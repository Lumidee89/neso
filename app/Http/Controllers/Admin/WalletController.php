<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Wallets/Index', [
            'wallets' => Wallet::with('user:id,first_name,last_name,email,user_type,status')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function freeze(Request $request, Wallet $wallet): RedirectResponse
    {
        abort_unless($request->user()->can('freeze-accounts'), 403);

        $wallet->user->update(['status' => 'suspended']);

        return back()->with('success', 'Account frozen.');
    }

    public function unfreeze(Request $request, Wallet $wallet): RedirectResponse
    {
        abort_unless($request->user()->can('freeze-accounts'), 403);

        $wallet->user->update(['status' => 'verified']);

        return back()->with('success', 'Account unfrozen.');
    }
}
