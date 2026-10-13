<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Contracts/Index', [
            'contracts' => Contract::with([
                'payer:id,first_name,last_name,email',
                'vendor:id,first_name,last_name,email',
                'escrowTransaction',
            ])->orderByDesc('created_at')->get(),
        ]);
    }
}
