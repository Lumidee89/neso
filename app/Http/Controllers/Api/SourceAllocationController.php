<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\SourceAllocation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SourceAllocationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type === 'vendor') {
            return response()->json(
                SourceAllocation::where('vendor_id', $user->id)
                    ->with(['contract:id,title,amount,payer_id,vendor_id', 'source:id,first_name,last_name,business_name'])
                    ->orderByDesc('created_at')
                    ->get()
            );
        }

        if ($user->user_type === 'source') {
            return response()->json(
                SourceAllocation::where('source_id', $user->id)
                    ->with([
                        'contract:id,title,payer_id,vendor_id',
                        'contract.payer:id,first_name,last_name,business_name',
                        'contract.vendor:id,first_name,last_name,business_name',
                    ])
                    ->orderByDesc('created_at')
                    ->get()
            );
        }

        return response()->json([]);
    }

    public function show(Request $request, SourceAllocation $sourceAllocation): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->id, [$sourceAllocation->vendor_id, $sourceAllocation->source_id], true), 403);

        if ($user->id === $sourceAllocation->vendor_id) {
            $sourceAllocation->load(['contract:id,title,amount,payer_id,vendor_id', 'source:id,first_name,last_name,business_name']);
        } else {
            $sourceAllocation->load([
                'contract:id,title,payer_id,vendor_id',
                'contract.payer:id,first_name,last_name,business_name',
                'contract.vendor:id,first_name,last_name,business_name',
            ]);
        }

        return response()->json($sourceAllocation);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->user_type === 'vendor', 403, 'Only vendors can allocate funds to a source.');

        $data = $request->validate([
            'contract_id' => ['required', 'exists:contracts,id'],
            'source_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $contract = Contract::findOrFail($data['contract_id']);
        abort_unless($contract->vendor_id === $user->id, 403);
        abort_unless($contract->status === 'paid', 422, 'Contract must be funded before allocating to a source.');

        $escrow = $contract->escrowTransaction;
        abort_unless($escrow && $escrow->status === 'locked', 422, "This contract's escrow is not available for allocation.");

        $source = User::where('id', $data['source_id'])->where('user_type', 'source')->firstOrFail();

        $alreadyAllocated = $contract->sourceAllocations()
            ->whereIn('status', ['pending', 'approved'])
            ->sum('allocated_amount');
        $remaining = (float) $contract->amount - (float) $alreadyAllocated;

        if ($data['amount'] > $remaining) {
            return response()->json([
                'message' => "You can only allocate up to \u{20a6}".number_format($remaining, 2).' of this contract\'s locked amount.',
            ], 422);
        }

        $allocation = SourceAllocation::create([
            'contract_id' => $contract->id,
            'vendor_id' => $user->id,
            'source_id' => $source->id,
            'allocated_amount' => $data['amount'],
            'transaction_code' => 'ALLOC-'.now()->year.'-'.Str::upper(Str::random(10)),
            'status' => 'pending',
        ]);

        $vendorName = $user->business_name ?? "{$user->first_name} {$user->last_name}";
        $this->notifications->notify(
            $source,
            'allocation_proposed',
            'New payment allocation',
            "{$vendorName} proposed to send you \u{20a6}".number_format($data['amount'], 2)." for \"{$contract->title}\".",
            ['allocation_id' => $allocation->id, 'contract_id' => $contract->id]
        );

        return response()->json(
            $allocation->load(['contract:id,title,amount,payer_id,vendor_id', 'source:id,first_name,last_name,business_name']),
            201
        );
    }

    public function accept(Request $request, SourceAllocation $sourceAllocation): JsonResponse
    {
        return $this->respond($request, $sourceAllocation, 'approved');
    }

    public function reject(Request $request, SourceAllocation $sourceAllocation): JsonResponse
    {
        return $this->respond($request, $sourceAllocation, 'rejected');
    }

    private function respond(Request $request, SourceAllocation $allocation, string $to): JsonResponse
    {
        $user = $request->user();
        abort_unless($allocation->source_id === $user->id, 403);
        abort_unless($allocation->status === 'pending', 422, 'This allocation has already been responded to.');

        $allocation->update(['status' => $to]);

        $contract = $allocation->contract;
        $sourceName = $user->business_name ?? "{$user->first_name} {$user->last_name}";
        $this->notifications->notify(
            $allocation->vendor,
            $to === 'approved' ? 'allocation_accepted' : 'allocation_rejected',
            $to === 'approved' ? 'Allocation accepted' : 'Allocation rejected',
            $to === 'approved'
                ? "{$sourceName} accepted your allocation of \u{20a6}".number_format($allocation->allocated_amount, 2)." for \"{$contract->title}\"."
                : "{$sourceName} rejected your allocation of \u{20a6}".number_format($allocation->allocated_amount, 2)." for \"{$contract->title}\".",
            ['allocation_id' => $allocation->id, 'contract_id' => $allocation->contract_id]
        );

        return response()->json(
            $allocation->fresh()->load([
                'contract:id,title,payer_id,vendor_id',
                'contract.payer:id,first_name,last_name,business_name',
                'contract.vendor:id,first_name,last_name,business_name',
            ])
        );
    }
}
