<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Dispute;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $disputes = Dispute::whereHas('contract', function ($query) use ($user) {
            $query->where('payer_id', $user->id)->orWhere('vendor_id', $user->id);
        })
            ->with(['contract:id,title,amount,payer_id,vendor_id', 'raisedBy:id,first_name,last_name,business_name'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($disputes);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contract_id' => ['required', 'exists:contracts,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $contract = Contract::findOrFail($data['contract_id']);
        abort_unless(in_array($user->id, [$contract->payer_id, $contract->vendor_id], true), 403);
        abort_unless($contract->status === 'paid', 422, 'Disputes can only be raised on funded contracts.');

        $hasOpenDispute = Dispute::where('contract_id', $contract->id)
            ->whereIn('status', ['open', 'under_review'])
            ->exists();
        abort_if($hasOpenDispute, 422, 'There is already an open dispute for this contract.');

        $dispute = Dispute::create([
            'contract_id' => $contract->id,
            'raised_by' => $user->id,
            'reason' => $data['reason'],
            'status' => 'open',
        ]);

        $contract->escrowTransaction?->update(['status' => 'disputed']);

        $otherPartyId = $user->id === $contract->payer_id ? $contract->vendor_id : $contract->payer_id;
        $raiserName = $user->business_name ?? "{$user->first_name} {$user->last_name}";

        $this->notifications->notify(
            User::findOrFail($otherPartyId),
            'dispute_raised',
            'Dispute raised',
            "{$raiserName} raised a dispute on \"{$contract->title}\".",
            ['contract_id' => $contract->id, 'dispute_id' => $dispute->id]
        );

        return response()->json($dispute->load('contract:id,title,amount'), 201);
    }
}
