<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\EscrowTransaction;
use App\Models\SourceAllocation;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DisputeController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => Dispute::with([
                'contract:id,title,amount',
                'raisedBy:id,first_name,last_name,email',
            ])->orderByDesc('created_at')->get(),
        ]);
    }

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        abort_unless($request->user()->can('manage-disputes'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:resolved_refund,resolved_release'],
            'resolution_note' => ['required', 'string', 'max:1000'],
        ]);

        $contract = $dispute->contract()->with(['payer', 'vendor', 'escrowTransaction'])->firstOrFail();
        $escrow = $contract->escrowTransaction;
        $adminId = $request->user()->id;

        $releasedAllocations = collect();

        DB::transaction(function () use ($contract, $escrow, $data, $adminId, &$releasedAllocations) {
            if (! $escrow || $escrow->status !== 'disputed') {
                return;
            }

            $payerWallet = $contract->payer->wallet;
            $vendorWallet = $contract->vendor->wallet;

            if ($data['status'] === 'resolved_refund') {
                $payerWallet->decrement('locked_balance', $contract->amount);
                $payerWallet->increment('available_balance', $contract->amount);
                $escrow->update(['status' => 'refunded', 'released_at' => now()]);
                $contract->update(['status' => 'cancelled']);

                WalletTransaction::create([
                    'wallet_id' => $payerWallet->id,
                    'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                    'type' => 'refund',
                    'amount' => $contract->amount,
                    'balance_type' => 'available',
                    'status' => 'success',
                    'description' => "Dispute refund for contract #{$contract->id}",
                    'related_type' => EscrowTransaction::class,
                    'related_id' => $escrow->id,
                ]);

                // The payer got everything back, so no source allocation on this contract can be honored.
                $contract->sourceAllocations()->whereIn('status', ['pending', 'approved'])->update(['status' => 'rejected']);
            } else {
                $approvedAllocations = $contract->sourceAllocations()->where('status', 'approved')->with('source')->get();
                $allocatedTotal = (float) $approvedAllocations->sum('allocated_amount');
                $vendorShare = (float) $contract->amount - $allocatedTotal;

                $payerWallet->decrement('locked_balance', $contract->amount);
                $escrow->update(['status' => 'released', 'released_at' => now()]);
                $contract->update(['status' => 'completed']);

                if ($vendorShare > 0) {
                    $vendorWallet->increment('available_balance', $vendorShare);

                    WalletTransaction::create([
                        'wallet_id' => $vendorWallet->id,
                        'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                        'type' => 'credit',
                        'amount' => $vendorShare,
                        'balance_type' => 'available',
                        'status' => 'success',
                        'description' => "Dispute resolution release for contract #{$contract->id}",
                        'related_type' => EscrowTransaction::class,
                        'related_id' => $escrow->id,
                    ]);
                }

                foreach ($approvedAllocations as $allocation) {
                    $sourceWallet = $allocation->source?->wallet;

                    if ($sourceWallet) {
                        $sourceWallet->increment('available_balance', $allocation->allocated_amount);

                        WalletTransaction::create([
                            'wallet_id' => $sourceWallet->id,
                            'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                            'type' => 'credit',
                            'amount' => $allocation->allocated_amount,
                            'balance_type' => 'available',
                            'status' => 'success',
                            'description' => "Source allocation payout for contract #{$contract->id}",
                            'related_type' => SourceAllocation::class,
                            'related_id' => $allocation->id,
                        ]);
                    }

                    $allocation->update(['status' => 'released']);
                }

                $contract->sourceAllocations()->where('status', 'pending')->update(['status' => 'rejected']);
                $releasedAllocations = $approvedAllocations;
            }

            $contract->statusLogs()->create([
                'from_status' => 'paid',
                'to_status' => $contract->fresh()->status,
                'changed_by' => $adminId,
            ]);
        });

        $dispute->update([
            'status' => $data['status'],
            'resolution_note' => $data['resolution_note'],
            'resolved_by' => $adminId,
            'resolved_at' => now(),
        ]);

        $outcome = $data['status'] === 'resolved_refund'
            ? 'refunded back to the payer.'
            : 'released to the vendor.';

        $this->notifications->notify(
            $contract->payer,
            'dispute_resolved',
            'Dispute resolved',
            "The dispute on \"{$contract->title}\" has been resolved: funds were {$outcome}",
            ['contract_id' => $contract->id, 'dispute_id' => $dispute->id]
        );
        $this->notifications->notify(
            $contract->vendor,
            'dispute_resolved',
            'Dispute resolved',
            "The dispute on \"{$contract->title}\" has been resolved: funds were {$outcome}",
            ['contract_id' => $contract->id, 'dispute_id' => $dispute->id]
        );

        foreach ($releasedAllocations as $allocation) {
            if ($allocation->source) {
                $this->notifications->notify(
                    $allocation->source,
                    'allocation_released',
                    'Payment received',
                    "You received \u{20a6}".number_format($allocation->allocated_amount, 2)." for \"{$contract->title}\".",
                    ['allocation_id' => $allocation->id, 'contract_id' => $contract->id]
                );
            }
        }

        return back()->with('success', 'Dispute resolved.');
    }
}
