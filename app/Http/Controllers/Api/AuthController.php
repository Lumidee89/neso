<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly NotificationService $notifications,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'user_type' => ['required', 'in:payer,vendor,source'],
            'address' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'status' => 'pending_verification',
        ]);

        $this->otpService->generate($user, 'email_verification');

        return response()->json([
            'message' => 'Registration successful. Please verify your email with the OTP sent.',
            'user_id' => $user->id,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
                'requires_email_verification' => true,
                'user_id' => $user->id,
            ], 403);
        }

        if ($user->twoFactor?->enabled) {
            return response()->json([
                'message' => 'Two-factor authentication required.',
                'requires_2fa' => true,
                'user_id' => $user->id,
            ]);
        }

        $this->notifications->notify(
            $user,
            'login',
            'New login',
            'You just logged in to your NESO account at '.now()->format('g:i A \o\n M j, Y').'.'
        );

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
