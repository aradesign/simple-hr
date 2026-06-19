<?php

use App\Http\Controllers\Admin\ApplicationImportController;
use App\Http\Controllers\Admin\ApplicationFormFieldController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\EmploymentApplicationController;
use App\Http\Controllers\Admin\HrTicketController;
use App\Http\Controllers\Admin\InterviewController;
use App\Http\Controllers\Admin\PersonController;
use App\Http\Controllers\Admin\PersonImportController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login']);
    });

    Route::middleware(['auth', 'hr'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::redirect('/', '/admin/dashboard');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::delete('persons/bulk', [PersonController::class, 'bulkDestroy'])->name('persons.bulk-destroy');
        Route::post('persons/import', [PersonImportController::class, 'store'])->name('persons.import.store');
        Route::post('persons/import/{importId}/process', [PersonImportController::class, 'process'])->name('persons.import.process');
        Route::resource('persons', PersonController::class);

        Route::get('applications/export', [EmploymentApplicationController::class, 'export'])->name('applications.export');
        Route::post('applications/import', [ApplicationImportController::class, 'store'])->name('applications.import.store');
        Route::post('applications/import/{importId}/process', [ApplicationImportController::class, 'process'])->name('applications.import.process');
        Route::delete('applications/bulk', [EmploymentApplicationController::class, 'bulkDestroy'])->name('applications.bulk-destroy');
        Route::get('applications', [EmploymentApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}/print', [EmploymentApplicationController::class, 'print'])->name('applications.print');
        Route::get('applications/{application}/download', [EmploymentApplicationController::class, 'download'])->name('applications.download');
        Route::get('applications/{application}', [EmploymentApplicationController::class, 'show'])->name('applications.show');
        Route::patch('applications/{application}/status', [EmploymentApplicationController::class, 'updateStatus'])->name('applications.update-status');
        Route::post('applications/{application}/schedule-interview', [EmploymentApplicationController::class, 'scheduleInterview'])->name('applications.schedule-interview');
        Route::delete('applications/{application}', [EmploymentApplicationController::class, 'destroy'])->name('applications.destroy');

        Route::delete('interviews/bulk', [InterviewController::class, 'bulkDestroy'])->name('interviews.bulk-destroy');
        Route::resource('interviews', InterviewController::class);

        Route::get('tickets', [HrTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/{ticket}', [HrTicketController::class, 'show'])->name('tickets.show');
        Route::patch('tickets/{ticket}', [HrTicketController::class, 'update'])->name('tickets.update');

        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::post('calendar', [CalendarController::class, 'store'])->name('calendar.store');
        Route::put('calendar/{event}', [CalendarController::class, 'update'])->name('calendar.update');
        Route::delete('calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/versions/{version}/download', [DocumentController::class, 'downloadVersion'])->name('documents.download-version');

        Route::resource('departments', DepartmentController::class);

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::get('form-fields', [ApplicationFormFieldController::class, 'index'])->name('form-fields.index');
        Route::patch('form-fields', [ApplicationFormFieldController::class, 'update'])->name('form-fields.update');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware('super_admin')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('appearance', [SettingsController::class, 'updateAppearance'])->name('appearance');
            Route::put('branding', [SettingsController::class, 'updateBranding'])->name('branding');
            Route::put('sms', [SettingsController::class, 'updateSms'])->name('sms');
            Route::put('sms-actions', [SettingsController::class, 'updateSmsActions'])->name('sms-actions');
            Route::post('sms/test', [SettingsController::class, 'testSms'])->name('sms.test');
            Route::post('sms/test-connection', [SettingsController::class, 'testSmsConnection'])->name('sms.test-connection');
            Route::delete('branding/file', [SettingsController::class, 'removeBranding'])->name('branding.remove');
            Route::put('texts', [SettingsController::class, 'updateTexts'])->name('texts');
        });
    });
});
