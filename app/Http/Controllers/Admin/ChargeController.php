<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargeTransaction;
use Inertia\Inertia;
use Inertia\Response;

class ChargeController extends Controller
{
    public function index(): Response
    {
        $charges = ChargeTransaction::with([
            'serviceCharge:id,name,type',
            'contract:id,title',
            'user:id,first_name,last_name,business_name,email',
        ])->orderByDesc('created_at')->get();

        return Inertia::render('Admin/Charges/Index', [
            'charges' => $charges,
            'totalCollected' => (float) $charges->sum('amount'),
        ]);
    }
}
