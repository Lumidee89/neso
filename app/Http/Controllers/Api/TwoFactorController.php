<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwoFactor;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly NotificationService $notifications,
    ) {}

    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $secret = $this->google2fa->generateSecretKey();

        $twoFactor = TwoFactor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => $secret,
                'recovery_codes' => collect()->times(8, fn () => Str::random(10))->all(),
                'enabled' => false,
            ]
        );

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $this->google2fa->getQRCodeUrl('NESO', $user->email, $secret),
            'recovery_codes' => $twoFactor->recovery_codes,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();
        $twoFactor = $user->twoFactor;

        if (! $twoFactor || ! $this->google2fa->verifyKey($twoFactor->secret, $data['code'])) {
            return response()->json(['message' => 'Invalid authentication code.'], 422);
        }

        $twoFactor->update(['enabled' => true, 'confirmed_at' => now()]);

        return response()->json(['message' => 'Two-factor authentication enabled.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'code' => ['required', 'string'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $twoFactor = $user->twoFactor;

        if (! $twoFactor?->enabled || ! $this->google2fa->verifyKey($twoFactor->secret, $data['code'])) {
            return response()->json(['message' => 'Invalid authentication code.'], 422);
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
}
