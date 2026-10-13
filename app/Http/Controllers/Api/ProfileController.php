<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json($user->fresh()->load('wallet', 'twoFactor'));
    }

    public function switchRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_type' => ['required', 'in:payer,vendor,source'],
        ]);

        $user = $request->user();
        abort_unless(in_array($user->user_type, ['payer', 'vendor', 'source'], true), 403);

        $user->update(['user_type' => $data['user_type']]);

        return response()->json($user->fresh()->load('wallet', 'twoFactor'));
    }
}
