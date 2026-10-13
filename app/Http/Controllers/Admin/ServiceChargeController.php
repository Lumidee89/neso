<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCharge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceChargeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ServiceCharges/Index', [
            'charges' => ServiceCharge::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('configure-charges'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:flat,percentage,tiered'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'applies_to' => ['required', 'in:all,contract,withdrawal,escrow_release'],
        ]);

        ServiceCharge::create([...$data, 'active' => true]);

        return back()->with('success', 'Service charge created.');
    }

    public function update(Request $request, ServiceCharge $serviceCharge): RedirectResponse
    {
        abort_unless($request->user()->can('configure-charges'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:flat,percentage,tiered'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'applies_to' => ['required', 'in:all,contract,withdrawal,escrow_release'],
        ]);

        $serviceCharge->update($data);

        return back()->with('success', 'Service charge updated.');
    }

    public function toggle(Request $request, ServiceCharge $serviceCharge): RedirectResponse
    {
        abort_unless($request->user()->can('configure-charges'), 403);

        $serviceCharge->update(['active' => ! $serviceCharge->active]);

        return back()->with('success', 'Service charge status updated.');
    }
}
