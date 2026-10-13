<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeTransaction;
use App\Models\Contract;
use App\Models\EscrowTransaction;
use App\Models\Setting;
use App\Models\SourceAllocation;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use App\Services\ServiceChargeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ServiceChargeCalculator $chargeCalculator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $contracts = Contract::where('payer_id', $user->id)
            ->orWhere('vendor_id', $user->id)
            ->with(['payer:id,first_name,last_name,business_name', 'vendor:id,first_name,last_name,business_name', 'escrowTransaction'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($contracts);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->user_type === 'payer', 403, 'Only payers can create contracts.');

        $data = $request->validate([
            'vendor_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'delivery_date' => ['nullable', 'date'],
            'terms' => ['nullable', 'string'],
        ]);

        $vendor = User::where('id', $data['vendor_id'])->where('user_type', 'vendor')->firstOrFail();

        $contract = Contract::create([
            'payer_id' => $user->id,
            'vendor_id' => $vendor->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'delivery_date' => $data['delivery_date'] ?? null,
            'terms' => $data['terms'] ?? null,
            'status' => 'sent',
        ]);

        $contract->statusLogs()->create([
            'from_status' => null,
            'to_status' => 'sent',
            'changed_by' => $user->id,
        ]);

        $payerName = $user->business_name ?? "{$user->first_name} {$user->last_name}";
        $this->notifications->notify(
            $vendor,
            'contract_started',
            'New contract received',
            "{$payerName} sent you a new contract: \"{$contract->title}\" for \u{20a6}".number_format($contract->amount, 2).'.',
            ['contract_id' => $contract->id]
        );

        return response()->json($contract->load(['payer:id,first_name,last_name,business_name', 'vendor:id,first_name,last_name,business_name']), 201);
    }

    public function show(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeParty($request, $contract);

        return response()->json(
            $contract->load(['payer:id,first_name,last_name,business_name', 'vendor:id,first_name,last_name,business_name', 'escrowTransaction', 'statusLogs'])
        );
    }

    public function accept(Request $request, Contract $contract): JsonResponse
    {
        return $this->transitionByVendor($request, $contract, 'accepted');
    }

    public function reject(Request $request, Contract $contract): JsonResponse
    {
        return $this->transitionByVendor($request, $contract, 'rejected');
    }

    public function fund(Request $request, Contract $contract): JsonResponse
    {
        $user = $request->user();
        abort_unless($contract->payer_id === $user->id, 403);
        abort_unless($contract->status === 'accepted', 422, 'Contract must be accepted before it can be funded.');

        $wallet = $user->wallet;
        $applicableCharges = $this->chargeCalculator->calculate('contract', (float) $contract->amount);
        $chargesTotal = $applicableCharges->sum('amount');
        $totalDue = (float) $contract->amount + $chargesTotal;

        if (! $wallet || $wallet->available_balance < $totalDue) {
            return response()->json(['message' => 'Insufficient available balance.'], 422);
        }

        DB::transaction(function () use ($contract, $wallet, $user, $applicableCharges, $chargesTotal) {
            $wallet->decrement('available_balance', $contract->amount);
            $wallet->increment('locked_balance', $contract->amount);

            $escrow = EscrowTransaction::create([
                'contract_id' => $contract->id,
                'amount' => $contract->amount,
                'status' => 'locked',
                'locked_at' => now(),
                'release_eligible_at' => now()->addSeconds((int) Setting::getValue('escrow_release_delay_seconds', 3600)),
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                'type' => 'lock',
                'amount' => $contract->amount,
                'balance_type' => 'locked',
                'status' => 'success',
                'description' => "Escrow lock for contract #{$contract->id}",
                'related_type' => EscrowTransaction::class,
                'related_id' => $escrow->id,
            ]);

            $contract->update(['status' => 'paid']);

            $contract->statusLogs()->create([
                'from_status' => 'accepted',
                'to_status' => 'paid',
                'changed_by' => $user->id,
            ]);

            if ($chargesTotal > 0) {
                $wallet->decrement('available_balance', $chargesTotal);

                foreach ($applicableCharges as $applicable) {
                    $chargeTransaction = ChargeTransaction::create([
                        'service_charge_id' => $applicable['charge']->id,
                        'contract_id' => $contract->id,
                        'user_id' => $user->id,
                        'context' => 'contract_funding',
                        'amount' => $applicable['amount'],
                    ]);

                    WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                        'type' => 'service_charge',
                        'amount' => $applicable['amount'],
                        'balance_type' => 'available',
                        'status' => 'success',
                        'description' => "{$applicable['charge']->name} for contract #{$contract->id}",
                        'related_type' => ChargeTransaction::class,
                        'related_id' => $chargeTransaction->id,
                    ]);
                }
            }
        });

        $contract->load(['payer', 'vendor']);
        $chargeNote = $chargesTotal > 0
            ? " A service charge of \u{20a6}".number_format($chargesTotal, 2).' was also deducted from your available balance.'
            : '';
        $this->notifications->notify(
            $contract->vendor,
            'contract_funded',
            'Contract funded',
            "\"{$contract->title}\" has been funded. \u{20a6}".number_format($contract->amount, 2).' is now locked in escrow.',
            ['contract_id' => $contract->id]
        );
        $this->notifications->notify(
            $contract->payer,
            'contract_funded',
            'Contract funded',
            "You funded \"{$contract->title}\". \u{20a6}".number_format($contract->amount, 2).' is now locked in escrow.'.$chargeNote,
            ['contract_id' => $contract->id, 'charges_total' => $chargesTotal]
        );

        return response()->json(
            $contract->fresh()->load(['payer:id,first_name,last_name,business_name', 'vendor:id,first_name,last_name,business_name', 'escrowTransaction'])
        );
    }

    public function release(Request $request, Contract $contract): JsonResponse
    {
        $user = $request->user();
        abort_unless($contract->payer_id === $user->id, 403);
        abort_unless($contract->status === 'paid', 422, 'Contract must be funded before it can be released.');

        $escrow = $contract->escrowTransaction;
        abort_if(! $escrow, 422, 'No escrow transaction found for this contract.');
        abort_unless($escrow->status === 'locked', 422, 'This contract\'s escrow is not in a releasable state.');

        $vendorWallet = $contract->vendor->wallet;
        abort_if(! $vendorWallet, 422, 'Vendor does not have a wallet yet.');

        $payerWallet = $user->wallet;
        $approvedAllocations = $contract->sourceAllocations()->where('status', 'approved')->with('source')->get();
        $allocatedTotal = (float) $approvedAllocations->sum('allocated_amount');
        $vendorShare = (float) $contract->amount - $allocatedTotal;

        DB::transaction(function () use ($contract, $escrow, $payerWallet, $vendorWallet, $user, $approvedAllocations, $vendorShare) {
            $payerWallet->decrement('locked_balance', $contract->amount);

            WalletTransaction::create([
                'wallet_id' => $payerWallet->id,
                'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                'type' => 'release',
                'amount' => $contract->amount,
                'balance_type' => 'locked',
                'status' => 'success',
                'description' => "Escrow release for contract #{$contract->id}",
                'related_type' => EscrowTransaction::class,
                'related_id' => $escrow->id,
            ]);

            if ($vendorShare > 0) {
                $vendorWallet->increment('available_balance', $vendorShare);

                WalletTransaction::create([
                    'wallet_id' => $vendorWallet->id,
                    'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                    'type' => 'credit',
                    'amount' => $vendorShare,
                    'balance_type' => 'available',
                    'status' => 'success',
                    'description' => "Escrow released for contract #{$contract->id}",
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

            // Any allocation the source never responded to can no longer be honored once escrow is released.
            $contract->sourceAllocations()->where('status', 'pending')->update(['status' => 'rejected']);

            $escrow->update(['status' => 'released', 'released_at' => now()]);
            $contract->update(['status' => 'completed']);

            $contract->statusLogs()->create([
                'from_status' => 'paid',
                'to_status' => 'completed',
                'changed_by' => $user->id,
            ]);
        });

        $contract->load(['payer', 'vendor']);
        $vendorNote = $allocatedTotal > 0
            ? "\"{$contract->title}\" was marked complete. \u{20a6}".number_format($vendorShare, 2)." has been added to your available balance (\u{20a6}".number_format($allocatedTotal, 2).' was allocated to your source(s)).'
            : "\"{$contract->title}\" was marked complete. \u{20a6}".number_format($vendorShare, 2).' has been added to your available balance.';
        $this->notifications->notify($contract->vendor, 'contract_completed', 'Contract completed', $vendorNote, ['contract_id' => $contract->id]);
        $this->notifications->notify(
            $contract->payer,
            'contract_completed',
            'Contract completed',
            "You released the funds for \"{$contract->title}\".",
            ['contract_id' => $contract->id]
        );

        foreach ($approvedAllocations as $allocation) {
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

        return response()->json(
            $contract->fresh()->load(['payer:id,first_name,last_name,business_name', 'vendor:id,first_name,last_name,business_name', 'escrowTransaction'])
        );
    }

    private function transitionByVendor(Request $request, Contract $contract, string $to): JsonResponse
    {
        $user = $request->user();
        abort_unless($contract->vendor_id === $user->id, 403);
        abort_unless($contract->status === 'sent', 422, 'Contract is not awaiting a response.');

        $contract->update(['status' => $to]);

        $contract->statusLogs()->create([
            'from_status' => 'sent',
            'to_status' => $to,
            'changed_by' => $user->id,
        ]);

        $vendorName = $user->business_name ?? "{$user->first_name} {$user->last_name}";
        $this->notifications->notify(
            $contract->payer,
            $to === 'accepted' ? 'contract_accepted' : 'contract_rejected',
            $to === 'accepted' ? 'Contract accepted' : 'Contract rejected',
            $to === 'accepted'
                ? "{$vendorName} accepted your contract \"{$contract->title}\". Fund it to lock the escrow."
                : "{$vendorName} rejected your contract \"{$contract->title}\".",
            ['contract_id' => $contract->id]
        );

        return response()->json($contract->fresh()->load(['payer:id,first_name,last_name,business_name', 'vendor:id,first_name,last_name,business_name']));
    }

    private function authorizeParty(Request $request, Contract $contract): void
    {
        $user = $request->user();
        abort_unless(in_array($user->id, [$contract->payer_id, $contract->vendor_id], true), 403);
    }
}
