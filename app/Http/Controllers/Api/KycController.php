<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\KycDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:nin,bvn,cac,government_id,business_verification,other'],
            'tier' => ['required', 'in:tier_1,tier_2,tier_3'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $request->file('file')->store("kyc/{$user->id}", 'local');

        $file = File::create([
            'path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'mime_type' => $request->file('file')->getMimeType(),
            'size' => $request->file('file')->getSize(),
            'uploaded_by' => $user->id,
        ]);

        $kycDocument = $user->kycDocuments()->create([
            'document_type' => $data['document_type'],
            'tier' => $data['tier'],
            'document_number' => $data['document_number'] ?? null,
            'file_id' => $file->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'KYC document submitted for review.',
            'document' => $kycDocument,
        ], 201);
    }
}
