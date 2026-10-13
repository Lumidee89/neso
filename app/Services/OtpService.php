<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function generate(User $user, string $purpose): OtpCode
    {
        $otp = $user->otpCodes()->create([
            'code' => (string) random_int(100000, 999999),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
        ]);

        // MVP: no SMS/email gateway configured yet, so the OTP is dispatched
        // via the log channel (dummy delivery), matching the PRD's MVP pattern.
        Log::info("OTP for {$user->email} ({$purpose}): {$otp->code}");

        return $otp;
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $otp = $user->otpCodes()
            ->where('purpose', $purpose)
            ->where('code', $code)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->isExpired()) {
            return false;
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }
}
