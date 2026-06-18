<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\SettingGroup;
use App\Http\Controllers\Controller;
use App\Services\Settings\SettingService;
use App\Services\Sms\SmsGatewayService;
use App\Support\SmsActionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly SmsGatewayService $smsGateway,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->input('tab', SettingGroup::Appearance->value);
        $validTabs = array_map(fn (SettingGroup $group) => $group->value, SettingGroup::cases());

        if (! in_array($tab, $validTabs, true)) {
            $tab = SettingGroup::Appearance->value;
        }

        return view('admin.settings.index', [
            'tab' => $tab,
            'settings' => $this->settings->all(),
            'groups' => SettingGroup::cases(),
            'smsStatus' => $this->smsGateway->status(),
            'smsActions' => SmsActionCatalog::all(),
        ]);
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_theme' => ['required', 'in:light,dark,system'],
            'primary_color' => ['required', 'in:cyan,blue,purple,magenta'],
            'show_scanlines' => ['boolean'],
            'sidebar_compact' => ['boolean'],
            'card_style' => ['required', 'in:rounded,sharp'],
        ]);

        $this->settings->setMany(SettingGroup::Appearance, [
            'default_theme' => $validated['default_theme'],
            'primary_color' => $validated['primary_color'],
            'show_scanlines' => $request->boolean('show_scanlines'),
            'sidebar_compact' => $request->boolean('sidebar_compact'),
            'card_style' => $validated['card_style'],
        ]);

        return redirect()->route('admin.settings.index', ['tab' => 'appearance'])
            ->with('success', 'تنظیمات ظاهری ذخیره شد.');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512', 'dimensions:max_width=512,max_height=512'],
        ]);

        $data = [
            'site_name' => $validated['site_name'],
            'site_tagline' => $validated['site_tagline'] ?? '',
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo');
        }

        if ($request->hasFile('logo_dark')) {
            $data['logo_dark_path'] = $request->file('logo_dark');
        }

        if ($request->hasFile('favicon')) {
            $data['favicon_path'] = $request->file('favicon');
        }

        $this->settings->setMany(SettingGroup::Branding, $data);

        return redirect()->route('admin.settings.index', ['tab' => 'branding'])
            ->with('success', 'تنظیمات برند و لوگو ذخیره شد.');
    }

    public function removeBranding(Request $request): RedirectResponse
    {
        $request->validate(['field' => ['required', 'in:logo_path,logo_dark_path,favicon_path']]);

        $this->settings->deleteBrandingFile($request->input('field'));

        return back()->with('success', 'فایل حذف شد.');
    }

    public function updateSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['boolean'],
            'provider' => ['required', 'in:sms_ir,ippanel'],
            'sms_ir_api_key' => ['nullable', 'string', 'max:255'],
            'sms_ir_line_number' => ['nullable', 'string', 'max:20'],
            'sms_ir_secret_key' => ['nullable', 'string', 'max:255'],
            'ippanel_api_key' => ['nullable', 'string', 'max:255'],
            'ippanel_originator' => ['nullable', 'string', 'max:20'],
            'ippanel_base_url' => ['nullable', 'url', 'max:255'],
        ]);

        if ($request->boolean('enabled')) {
            $this->validateSmsCredentials($validated);
        }

        $this->settings->setMany(SettingGroup::Sms, [
            'enabled' => $request->boolean('enabled'),
            'provider' => $validated['provider'],
            'sms_ir_api_key' => $validated['sms_ir_api_key'] ?? '',
            'sms_ir_line_number' => $validated['sms_ir_line_number'] ?? '',
            'sms_ir_secret_key' => $validated['sms_ir_secret_key'] ?? '',
            'ippanel_api_key' => $validated['ippanel_api_key'] ?? '',
            'ippanel_originator' => $validated['ippanel_originator'] ?? '',
            'ippanel_base_url' => $validated['ippanel_base_url'] ?? 'https://edge.ippanel.com/v1',
        ]);

        return redirect()->route('admin.settings.index', ['tab' => 'sms'])
            ->with('success', 'تنظیمات اتصال پیامک ذخیره شد.');
    }

    public function updateSmsActions(Request $request): RedirectResponse
    {
        $rules = [
            'texts' => ['required', 'array'],
            'patterns' => ['nullable', 'array'],
            'patterns.sms_ir' => ['nullable', 'array'],
            'patterns.ippanel' => ['nullable', 'array'],
        ];

        foreach (SmsActionCatalog::all() as $action) {
            $rules["texts.{$action['text_key']}"] = ['required', 'string', 'max:500'];
            $rules["patterns.sms_ir.{$action['key']}"] = ['nullable', 'string', 'max:50'];
            $rules["patterns.ippanel.{$action['key']}"] = ['nullable', 'string', 'max:50'];
        }

        $validated = $request->validate($rules);

        $this->settings->setMany(SettingGroup::Texts, $validated['texts']);

        $patternData = [];

        foreach (SmsActionCatalog::all() as $action) {
            $patternData[SmsActionCatalog::smsIrPatternSettingKey($action['key'])] =
                $validated['patterns']['sms_ir'][$action['key']] ?? '';
            $patternData[SmsActionCatalog::ippanelPatternSettingKey($action['key'])] =
                $validated['patterns']['ippanel'][$action['key']] ?? '';
        }

        $this->settings->setMany(SettingGroup::Sms, $patternData);

        return redirect()->route('admin.settings.index', ['tab' => 'sms'])
            ->with('success', 'متون و کدهای قالب پیامک ذخیره شد.');
    }

    public function testSmsConnection(): RedirectResponse
    {
        $result = $this->smsGateway->testConnection();

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? 'نتیجه نامشخص'
        );
    }

    public function testSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ]);

        if (! $this->smsGateway->isEnabled()) {
            return back()->with('error', 'ابتدا ارسال پیامک را فعال کنید.');
        }

        $siteName = $this->settings->get('branding', 'site_name', 'سامانه منابع انسانی');

        $send = $this->smsGateway->sendForAction(
            'sms_test',
            $validated['test_mobile'],
            $this->settings->renderForAction('sms_test', [
                'code' => '123456',
                'site_name' => $siteName,
            ]),
            [
                'code' => '123456',
                'site_name' => $siteName,
            ],
        );

        return back()->with(
            ($send['success'] ?? false) ? 'success' : 'error',
            $send['message'] ?? 'ارسال ناموفق'
        );
    }

    public function updateTexts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'welcome_message' => ['required', 'string', 'max:500'],
            'dashboard_greeting' => ['required', 'string', 'max:100'],
            'login_title' => ['required', 'string', 'max:200'],
            'login_subtitle' => ['required', 'string', 'max:300'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'recruitment_title' => ['required', 'string', 'max:200'],
            'recruitment_subtitle' => ['required', 'string', 'max:300'],
            'portal_title' => ['required', 'string', 'max:200'],
            'portal_subtitle' => ['required', 'string', 'max:300'],
        ]);

        $this->settings->setMany(SettingGroup::Texts, $validated);

        return redirect()->route('admin.settings.index', ['tab' => 'texts'])
            ->with('success', 'تنظیمات متون ذخیره شد.');
    }

    private function validateSmsCredentials(array $validated): void
    {
        if ($validated['provider'] === 'sms_ir' && empty($validated['sms_ir_api_key'])) {
            throw ValidationException::withMessages([
                'sms_ir_api_key' => 'برای فعال‌سازی sms.ir، API Key الزامی است.',
            ]);
        }

        if ($validated['provider'] === 'ippanel' && empty($validated['ippanel_api_key'])) {
            throw ValidationException::withMessages([
                'ippanel_api_key' => 'برای فعال‌سازی ippanel، API Key الزامی است.',
            ]);
        }
    }
}
