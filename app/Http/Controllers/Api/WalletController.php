<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function fund(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        abort_if(! $wallet, 422, 'You need a verified account with a wallet before you can fund it.');

        $transaction = DB::transaction(function () use ($wallet, $data) {
            $wallet->increment('available_balance', $data['amount']);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'reference' => 'TXN-'.now()->year.'-'.Str::upper(Str::random(10)),
                'type' => 'credit',
                'amount' => $data['amount'],
                'balance_type' => 'available',
                'status' => 'success',
                'description' => 'Wallet top-up',
            ]);
        });

        $this->notifications->notify(
            $user,
            'wallet_funded',
            'Wallet funded',
            "Your wallet was credited with \u{20a6}".number_format($data['amount'], 2).'.',
            ['amount' => $data['amount'], 'transaction_id' => $transaction->id]
        );

        return response()->json([
            'wallet' => $wallet->fresh(),
            'transaction' => $transaction,
        ], 201);
    }
}
