<?php

use App\Http\Controllers\Recruitment\ApplicationController;
use App\Http\Controllers\Recruitment\DashboardController;
use App\Http\Controllers\Recruitment\OtpAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment')->name('recruitment.')->group(function () {
    Route::get('login', [OtpAuthController::class, 'showLogin'])->name('login');
    Route::get('verify', [OtpAuthController::class, 'showVerify'])->name('verify');
    Route::post('otp/request', [OtpAuthController::class, 'requestOtp'])->name('otp.request');
    Route::post('otp/verify', [OtpAuthController::class, 'verifyOtp'])->name('otp.verify');

    Route::middleware('recruitment.auth')->group(function () {
        Route::post('logout', [OtpAuthController::class, 'logout'])->name('logout');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('applications/{application}/form', [ApplicationController::class, 'edit'])->name('applications.form');
        Route::put('applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
        Route::get('applications/{application}/status', [ApplicationController::class, 'status'])->name('applications.status');
    });
});
