<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ChargeController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\ServiceChargeController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:super-admin|compliance-officer|support-agent'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/kyc', [KycController::class, 'index'])->name('kyc.index');
        Route::get('/kyc/{kyc}/document', [KycController::class, 'viewDocument'])->name('kyc.document');
        Route::post('/kyc/{kyc}/approve', [KycController::class, 'approve'])->name('kyc.approve');
        Route::post('/kyc/{kyc}/reject', [KycController::class, 'reject'])->name('kyc.reject');

        Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');

        Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::post('/wallets/{wallet}/freeze', [WalletController::class, 'freeze'])->name('wallets.freeze');
        Route::post('/wallets/{wallet}/unfreeze', [WalletController::class, 'unfreeze'])->name('wallets.unfreeze');

        Route::get('/disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::post('/disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');

        Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');

        Route::get('/charges', [ChargeController::class, 'index'])->name('charges.index');

        Route::get('/service-charges', [ServiceChargeController::class, 'index'])->name('service-charges.index');
        Route::post('/service-charges', [ServiceChargeController::class, 'store'])->name('service-charges.store');
        Route::put('/service-charges/{serviceCharge}', [ServiceChargeController::class, 'update'])->name('service-charges.update');
        Route::post('/service-charges/{serviceCharge}/toggle', [ServiceChargeController::class, 'toggle'])->name('service-charges.toggle');
    });
