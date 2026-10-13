<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SourceAllocationController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/otp/request', [OtpController::class, 'request']);
    Route::post('/otp/verify', [OtpController::class, 'verify']);

    Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);

    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user()->load('wallet', 'twoFactor'));
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/switch-role', [ProfileController::class, 'switchRole']);

        Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
        Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm']);

        Route::post('/kyc', [KycController::class, 'store']);

        Route::post('/wallet/fund', [WalletController::class, 'fund']);

        Route::get('/withdrawals', [WithdrawalController::class, 'index']);
        Route::post('/withdrawals', [WithdrawalController::class, 'store']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::get('/vendors', [VendorController::class, 'index']);
        Route::get('/sources', [SourceController::class, 'index']);

        Route::get('/source-allocations', [SourceAllocationController::class, 'index']);
        Route::post('/source-allocations', [SourceAllocationController::class, 'store']);
        Route::get('/source-allocations/{sourceAllocation}', [SourceAllocationController::class, 'show']);
        Route::post('/source-allocations/{sourceAllocation}/accept', [SourceAllocationController::class, 'accept']);
        Route::post('/source-allocations/{sourceAllocation}/reject', [SourceAllocationController::class, 'reject']);

        Route::get('/disputes', [DisputeController::class, 'index']);
        Route::post('/disputes', [DisputeController::class, 'store']);

        Route::get('/contracts', [ContractController::class, 'index']);
        Route::post('/contracts', [ContractController::class, 'store']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show']);
        Route::post('/contracts/{contract}/accept', [ContractController::class, 'accept']);
        Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject']);
        Route::post('/contracts/{contract}/fund', [ContractController::class, 'fund']);
        Route::post('/contracts/{contract}/release', [ContractController::class, 'release']);
    });
});
