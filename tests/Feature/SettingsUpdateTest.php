<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Settings\SettingService;
use App\Support\SmsActionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_active_tab_content(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->withoutVite()
            ->get(route('admin.settings.index', ['tab' => 'branding']))
            ->assertOk()
            ->assertSee('برند، لوگو و فاوآیکون')
            ->assertSee('name="site_name"', false);
    }

    public function test_sms_tab_lists_all_actions(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)
            ->withoutVite()
            ->get(route('admin.settings.index', ['tab' => 'sms']));

        $response->assertOk();

        foreach (SmsActionCatalog::all() as $action) {
            $response->assertSee($action['label']);
        }
    }

    public function test_super_admin_can_save_appearance_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.appearance'), [
                'default_theme' => 'light',
                'primary_color' => 'purple',
                'show_scanlines' => '1',
                'sidebar_compact' => '0',
                'card_style' => 'sharp',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'appearance']));

        $settings = app(SettingService::class)->group('appearance');

        $this->assertSame('light', $settings['default_theme']);
        $this->assertSame('purple', $settings['primary_color']);
        $this->assertFalse($settings['sidebar_compact']);
        $this->assertSame('sharp', $settings['card_style']);
    }

    public function test_super_admin_can_save_sms_action_templates(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $payload = ['texts' => [], 'patterns' => ['sms_ir' => [], 'ippanel' => []]];

        foreach (SmsActionCatalog::all() as $action) {
            $payload['texts'][$action['text_key']] = 'متن '.$action['key'];
            $payload['patterns']['sms_ir'][$action['key']] = '111';
            $payload['patterns']['ippanel'][$action['key']] = 'abc';
        }

        $this->actingAs($admin)
            ->put(route('admin.settings.sms-actions'), $payload)
            ->assertRedirect(route('admin.settings.index', ['tab' => 'sms']));

        $settings = app(SettingService::class);

        $this->assertSame('متن otp_recruitment', $settings->get('texts', 'otp_recruitment_sms'));
        $this->assertSame('111', $settings->get('sms', SmsActionCatalog::smsIrPatternSettingKey('otp_recruitment')));
        $this->assertSame('abc', $settings->get('sms', SmsActionCatalog::ippanelPatternSettingKey('otp_recruitment')));
    }

    public function test_super_admin_can_save_text_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.texts'), [
                'welcome_message' => 'پیام تست',
                'dashboard_greeting' => 'درود',
                'login_title' => 'ورود تست',
                'login_subtitle' => 'زیرعنوان تست',
                'footer_text' => 'فوتر تست',
                'recruitment_title' => 'استخدام تست',
                'recruitment_subtitle' => 'زیرعنوان استخدام',
                'portal_title' => 'پورتال تست',
                'portal_subtitle' => 'زیرعنوان پورتال',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'texts']));

        $this->assertSame('پیام تست', app(SettingService::class)->get('texts', 'welcome_message'));
    }
}
