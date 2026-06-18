<?php

namespace App\Helpers;

use App\Services\Settings\SettingService;

class SettingsHelper
{
    public static function text(string $key, array $vars = [], ?string $default = null): string
    {
        $template = app(SettingService::class)->get('texts', $key, $default ?? '');

        foreach ($vars as $name => $value) {
            $template = str_replace('{'.$name.'}', (string) $value, $template);
        }

        return $template;
    }

    public static function appearance(): array
    {
        return app(SettingService::class)->group('appearance');
    }

    public static function primaryColorCss(): string
    {
        $color = app(SettingService::class)->get('appearance', 'primary_color', 'cyan');

        return match ($color) {
            'blue' => '--accent: #2563eb; --accent-light: #3b82f6; --accent-dark: #1d4ed8; --accent-glow: rgba(37, 99, 235, 0.35);',
            'purple' => '--accent: #7c3aed; --accent-light: #a855f7; --accent-dark: #6d28d9; --accent-glow: rgba(124, 58, 237, 0.35);',
            'magenta' => '--accent: #db2777; --accent-light: #ec4899; --accent-dark: #be185d; --accent-glow: rgba(219, 39, 119, 0.35);',
            default => '--accent: #0891b2; --accent-light: #22d3ee; --accent-dark: #0e7490; --accent-glow: rgba(8, 145, 178, 0.35);',
        };
    }
}
