<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'purpose' => ['required', 'in:login,withdrawal,source_debit_approval,password_reset,phone_verification,email_verification'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        $this->otpService->generate($user, $data['purpose']);

        return response()->json(['message' => 'OTP sent.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'purpose' => ['required', 'in:login,withdrawal,source_debit_approval,password_reset,phone_verification,email_verification'],
            'code' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        if (! $this->otpService->verify($user, $data['purpose'], $data['code'])) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        if ($data['purpose'] === 'email_verification' && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($data['purpose'] === 'phone_verification' && ! $user->phone_verified_at) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return response()->json(['message' => 'OTP verified.']);
    }
}
