<?php

use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\OtpAuthController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('login', [OtpAuthController::class, 'showLogin'])->name('login');
    Route::get('verify', [OtpAuthController::class, 'showVerify'])->name('verify');
    Route::post('otp/request', [OtpAuthController::class, 'requestOtp'])->name('otp.request');
    Route::post('otp/verify', [OtpAuthController::class, 'verifyOtp'])->name('otp.verify');

    Route::middleware('portal.auth')->group(function () {
        Route::post('logout', [OtpAuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('documents', [DocumentController::class, 'index'])->name('documents');
        Route::get('documents/{document}/versions/{version}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('notifications', [DashboardController::class, 'notifications'])->name('notifications');
        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    });
});
