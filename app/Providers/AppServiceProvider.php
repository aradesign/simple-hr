<?php

namespace App\Providers;

use App\Models\EmploymentRecord;
use App\Models\Person;
use App\Models\User;
use App\Observers\EmploymentRecordObserver;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        EmploymentRecord::observe(EmploymentRecordObserver::class);

        View::composer([
            'components.header',
            'components.sidebar',
            'components.layouts.app',
            'components.layouts.guest',
            'auth.login',
            'recruitment.*',
            'portal.*',
        ], function ($view) {
            $settings = app(SettingService::class);
            $branding = $settings->group('branding');

            $view->with([
                'appSettings' => $settings->all(),
                'siteName' => $branding['site_name'] ?? 'سامانه منابع انسانی',
                'logoUrl' => $settings->brandingUrl($branding['logo_path'] ?? null),
                'logoDarkUrl' => $settings->brandingUrl($branding['logo_dark_path'] ?? null),
                'faviconUrl' => $settings->brandingUrl($branding['favicon_path'] ?? null),
            ]);
        });
    }
}
