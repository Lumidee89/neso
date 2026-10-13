<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Kyc/Index', [
            'documents' => KycDocument::with(['user:id,first_name,last_name,email,user_type,status', 'file'])
                ->orderBy('status')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function viewDocument(Request $request, KycDocument $kyc): StreamedResponse
    {
        abort_unless($request->user()->can('approve-kyc'), 403);
        abort_if(! $kyc->file, 404);

        return Storage::disk('local')->response($kyc->file->path, $kyc->file->original_name);
    }

    public function approve(Request $request, KycDocument $kyc): RedirectResponse
    {
        abort_unless($request->user()->can('approve-kyc'), 403);

        $kyc->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $hasPending = KycDocument::where('user_id', $kyc->user_id)
            ->where('status', 'pending')
            ->exists();

        if (! $hasPending) {
            $kyc->user->update(['status' => 'verified']);
            $this->notifications->notify(
                $kyc->user,
                'kyc_approved',
                'KYC approved',
                'Your KYC document has been approved and your account is now verified.'
            );
        } else {
            $this->notifications->notify(
                $kyc->user,
                'kyc_approved',
                'Document approved',
                'One of your submitted KYC documents has been approved.'
            );
        }

        return back()->with('success', 'KYC document approved.');
    }

    public function reject(Request $request, KycDocument $kyc): RedirectResponse
    {
        abort_unless($request->user()->can('approve-kyc'), 403);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $kyc->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'KYC document rejected.');
    }
}
