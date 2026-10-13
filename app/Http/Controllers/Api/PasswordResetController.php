<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        $user = User::where('email', $data['email'])->firstOrFail();

        $this->otpService->generate($user, 'password_reset');

        return response()->json(['message' => 'A password reset OTP has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        if (! $this->otpService->verify($user, 'password_reset', $data['code'])) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
