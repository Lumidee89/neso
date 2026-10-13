<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Withdrawals/Index', [
            'withdrawals' => Withdrawal::with('user:id,first_name,last_name,business_name,email')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function approve(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        abort_unless($request->user()->can('manage-wallets'), 403);
        abort_unless($withdrawal->status === 'pending', 422, 'This withdrawal has already been processed.');

        $wallet = $withdrawal->wallet;
        abort_if($wallet->available_balance < $withdrawal->amount, 422, 'Vendor no longer has sufficient available balance.');

        DB::transaction(function () use ($withdrawal, $wallet) {
            $wallet->decrement('available_balance', $withdrawal->amount);
            $withdrawal->update(['status' => 'completed']);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'reference' => $withdrawal->reference,
                'type' => 'withdrawal',
                'amount' => $withdrawal->amount,
                'balance_type' => 'available',
                'status' => 'success',
                'description' => "Withdrawal to {$withdrawal->destination_bank} ({$withdrawal->destination_account_number})",
            ]);
        });

        $this->notifications->notify(
            $withdrawal->user,
            'withdrawal_approved',
            'Withdrawal approved',
            "Your withdrawal of \u{20a6}".number_format($withdrawal->amount, 2).' has been approved and processed.',
            ['withdrawal_id' => $withdrawal->id]
        );

        return back()->with('success', 'Withdrawal approved.');
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        abort_unless($request->user()->can('manage-wallets'), 403);
        abort_unless($withdrawal->status === 'pending', 422, 'This withdrawal has already been processed.');

        $withdrawal->update(['status' => 'failed']);

        $this->notifications->notify(
            $withdrawal->user,
            'withdrawal_rejected',
            'Withdrawal rejected',
            "Your withdrawal request of \u{20a6}".number_format($withdrawal->amount, 2).' was rejected.',
            ['withdrawal_id' => $withdrawal->id]
        );

        return back()->with('success', 'Withdrawal rejected.');
    }
}
