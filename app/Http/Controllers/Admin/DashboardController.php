<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Dispute;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users_total' => User::count(),
                'users_pending_verification' => User::where('status', 'pending_verification')->count(),
                'users_verified' => User::where('status', 'verified')->count(),
                'users_suspended' => User::where('status', 'suspended')->count(),
                'kyc_pending' => KycDocument::where('status', 'pending')->count(),
                'disputes_open' => Dispute::whereIn('status', ['open', 'under_review'])->count(),
                'contracts_total' => Contract::count(),
                'wallet_available_total' => (float) Wallet::sum('available_balance'),
                'wallet_locked_total' => (float) Wallet::sum('locked_balance'),
                'wallet_pending_total' => (float) Wallet::sum('pending_balance'),
            ],
        ]);
    }
}
