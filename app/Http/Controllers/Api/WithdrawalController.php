<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->withdrawals()->orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'destination_bank' => ['required', 'string', 'max:255'],
            'destination_account_number' => ['required', 'string', 'max:20'],
            'destination_account_name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        abort_if(! $wallet, 422, 'You need a wallet before you can request a withdrawal.');
        abort_if($wallet->available_balance < $data['amount'], 422, 'Insufficient available balance.');

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $data['amount'],
            'status' => 'pending',
            'destination_bank' => $data['destination_bank'],
            'destination_account_number' => $data['destination_account_number'],
            'destination_account_name' => $data['destination_account_name'],
            'reference' => 'WD-'.now()->year.'-'.Str::upper(Str::random(10)),
        ]);

        return response()->json($withdrawal, 201);
    }
}
