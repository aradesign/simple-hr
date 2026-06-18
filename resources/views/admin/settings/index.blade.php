<x-layouts.app title="تنظیمات سیستم">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">تنظیمات سیستم</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">مدیریت ظاهر، برند، پیامک و متون سامانه</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-slate-200 dark:border-cyan-500/20 pb-2">
            @foreach($groups as $group)
                <a href="{{ route('admin.settings.index', ['tab' => $group->value]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $tab === $group->value ? 'cyber-nav-active' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-cyan-500/5' }}">
                    {{ $group->label() }}
                </a>
            @endforeach
        </div>

        @if($tab === 'appearance')
            <x-card title="ظاهر و تم">
                <form method="POST" action="{{ route('admin.settings.appearance') }}" class="space-y-4 max-w-lg">
                    @csrf @method('PUT')
                    <x-form.select label="تم پیش‌فرض" name="default_theme" required>
                        <option value="dark" @selected(($settings['appearance']['default_theme'] ?? 'dark') === 'dark')>تیره</option>
                        <option value="light" @selected(($settings['appearance']['default_theme'] ?? '') === 'light')>روشن</option>
                        <option value="system" @selected(($settings['appearance']['default_theme'] ?? '') === 'system')>مطابق سیستم‌عامل</option>
                    </x-form.select>
                    <x-form.select label="رنگ اصلی" name="primary_color" required>
                        @foreach(['cyan' => 'فیروزه‌ای', 'blue' => 'آبی', 'purple' => 'بنفش', 'magenta' => 'صورتی'] as $val => $label)
                            <option value="{{ $val }}" @selected(($settings['appearance']['primary_color'] ?? 'cyan') === $val)>{{ $label }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.select label="استایل کارت‌ها" name="card_style" required>
                        <option value="rounded" @selected(($settings['appearance']['card_style'] ?? 'rounded') === 'rounded')>گوشه گرد</option>
                        <option value="sharp" @selected(($settings['appearance']['card_style'] ?? '') === 'sharp')>گوشه تیز</option>
                    </x-form.select>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="show_scanlines" value="1" class="rounded border-slate-400 text-cyan-600" @checked($settings['appearance']['show_scanlines'] ?? true)>
                        نمایش افکت scanline در دارک مود
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="sidebar_compact" value="1" class="rounded border-slate-400 text-cyan-600" @checked($settings['appearance']['sidebar_compact'] ?? false)>
                        منوی کناری فشرده
                    </label>
                    <x-button type="submit" variant="primary">ذخیره</x-button>
                </form>
            </x-card>
        @endif

        @if($tab === 'branding')
            <x-card title="برند، لوگو و فاوآیکون">
                <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" class="space-y-4 max-w-2xl">
                    @csrf @method('PUT')
                    <x-form.input label="نام سایت" name="site_name" :value="$settings['branding']['site_name'] ?? ''" required />
                    <x-form.input label="شعار / توضیح کوتاه" name="site_tagline" :value="$settings['branding']['site_tagline'] ?? ''" />

                    @foreach([
                        'logo' => ['key' => 'logo_path', 'label' => 'لوگوی روشن (برای پس‌زمینه روشن)'],
                        'logo_dark' => ['key' => 'logo_dark_path', 'label' => 'لوگوی تیره (برای دارک مود)'],
                        'favicon' => ['key' => 'favicon_path', 'label' => 'فاوآیکون'],
                    ] as $field => $meta)
                        <div class="p-4 rounded-lg border border-slate-200 dark:border-cyan-500/15 space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $meta['label'] }}</label>
                            @if(!empty($settings['branding'][$meta['key']]))
                                <div class="flex items-center gap-3">
                                    <img src="{{ app(\App\Services\Settings\SettingService::class)->brandingUrl($settings['branding'][$meta['key']]) }}"
                                         alt="{{ $meta['label'] }}"
                                         class="h-10 rounded bg-slate-100 dark:bg-slate-800 p-1">
                                    <x-button type="button" variant="secondary" class="text-xs"
                                              onclick="if(confirm('حذف این فایل؟')) document.getElementById('delete-{{ $meta['key'] }}').submit()">
                                        حذف
                                    </x-button>
                                </div>
                            @endif
                            <input type="file" name="{{ $field }}" accept="image/*" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                        </div>
                    @endforeach

                    <x-button type="submit" variant="primary">ذخیره</x-button>
                </form>

                @foreach(['logo_path', 'logo_dark_path', 'favicon_path'] as $deleteField)
                    <form id="delete-{{ $deleteField }}" method="POST" action="{{ route('admin.settings.branding.remove') }}" class="hidden">
                        @csrf @method('DELETE')
                        <input type="hidden" name="field" value="{{ $deleteField }}">
                    </form>
                @endforeach
            </x-card>
        @endif

        @if($tab === 'sms')
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <x-card title="اتصال پنل پیامکی">
                            <form method="POST" action="{{ route('admin.settings.sms') }}" class="space-y-4">
                                @csrf @method('PUT')
                                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-400 text-cyan-600" @checked($settings['sms']['enabled'] ?? false)>
                                    فعال‌سازی ارسال واقعی پیامک
                                </label>
                                <x-form.select label="ارائه‌دهنده" name="provider" required>
                                    <option value="sms_ir" @selected(($settings['sms']['provider'] ?? 'sms_ir') === 'sms_ir')>sms.ir</option>
                                    <option value="ippanel" @selected(($settings['sms']['provider'] ?? '') === 'ippanel')>ippanel.com</option>
                                </x-form.select>

                                <div class="border-t border-slate-200 dark:border-cyan-500/15 pt-4 space-y-3">
                                    <h3 class="font-semibold text-slate-800 dark:text-white">sms.ir</h3>
                                    <p class="text-xs text-slate-500">API: <code class="text-accent">https://api.sms.ir/v1</code> — هدر <code>x-api-key</code></p>
                                    <x-form.input label="API Key" name="sms_ir_api_key" :value="$settings['sms']['sms_ir_api_key'] ?? ''" />
                                    <x-form.input label="شماره خط (ارسال متنی)" name="sms_ir_line_number" :value="$settings['sms']['sms_ir_line_number'] ?? ''" />
                                    <x-form.input label="Secret Key (اختیاری)" name="sms_ir_secret_key" :value="$settings['sms']['sms_ir_secret_key'] ?? ''" />
                                </div>

                                <div class="border-t border-slate-200 dark:border-cyan-500/15 pt-4 space-y-3">
                                    <h3 class="font-semibold text-slate-800 dark:text-white">ippanel.com</h3>
                                    <p class="text-xs text-slate-500">Edge API: <code class="text-accent">https://edge.ippanel.com/v1</code></p>
                                    <x-form.input label="API Key / Token" name="ippanel_api_key" :value="$settings['sms']['ippanel_api_key'] ?? ''" />
                                    <x-form.input label="شماره ارسال‌کننده (Originator)" name="ippanel_originator" :value="$settings['sms']['ippanel_originator'] ?? ''" placeholder="+983000505" />
                                    <x-form.input label="Base URL" name="ippanel_base_url" :value="$settings['sms']['ippanel_base_url'] ?? 'https://edge.ippanel.com/v1'" />
                                </div>

                                <p class="text-xs text-slate-500">شناسه قالب‌های هر اکشن در بخش پایین همین صفحه تنظیم می‌شود.</p>
                                <x-button type="submit" variant="primary">ذخیره اتصال</x-button>
                            </form>
                        </x-card>
                    </div>

                    <div class="space-y-6">
                        <x-card title="وضعیت">
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">ارسال پیامک</dt>
                                    <dd>
                                        <x-badge :variant="($smsStatus['enabled'] ?? false) ? 'success' : 'default'">
                                            {{ ($smsStatus['enabled'] ?? false) ? 'فعال' : 'غیرفعال' }}
                                        </x-badge>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">ارائه‌دهنده</dt>
                                    <dd class="font-medium">{{ $smsStatus['provider_label'] ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">پیکربندی</dt>
                                    <dd>
                                        <x-badge :variant="($smsStatus['configured'] ?? false) ? 'success' : 'warning'">
                                            {{ ($smsStatus['configured'] ?? false) ? 'کامل' : 'ناقص' }}
                                        </x-badge>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">تعداد اکشن</dt>
                                    <dd class="font-medium">{{ $smsStatus['actions_count'] ?? count($smsActions) }}</dd>
                                </div>
                            </dl>
                        </x-card>

                        <x-card title="تست اتصال">
                            <form method="POST" action="{{ route('admin.settings.sms.test-connection') }}" class="space-y-3">
                                @csrf
                                <p class="text-sm text-slate-600 dark:text-slate-400">بررسی API Key بدون ارسال پیامک.</p>
                                <x-button type="submit" variant="secondary" class="w-full">بررسی اتصال</x-button>
                            </form>
                        </x-card>

                        <x-card title="ارسال آزمایشی">
                            <form method="POST" action="{{ route('admin.settings.sms.test') }}" class="space-y-4">
                                @csrf
                                <p class="text-sm text-slate-600 dark:text-slate-400">از اکشن <code class="text-accent">sms_test</code> استفاده می‌کند.</p>
                                <x-form.input label="موبایل تست" name="test_mobile" placeholder="09121234567" required />
                                <x-button type="submit" variant="primary" class="w-full">ارسال پیام آزمایشی</x-button>
                            </form>
                        </x-card>
                    </div>
                </div>

                <x-card title="اکشن‌ها، متون و کدهای قالب پیامک">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
                        هر ردیف یک رویداد واقعی در سیستم است. متن پیامک را ویرایش کنید، قالب را در پنل sms.ir یا ippanel بسازید، سپس شناسه/کد قالب را وارد کنید.
                    </p>

                    <form method="POST" action="{{ route('admin.settings.sms-actions') }}" class="space-y-4">
                        @csrf @method('PUT')

                        @foreach($smsActions as $action)
                            <x-settings.sms-action-card :action="$action" :settings="$settings" />
                        @endforeach

                        <div class="flex justify-end pt-2">
                            <x-button type="submit" variant="primary">ذخیره متون و قالب‌ها</x-button>
                        </div>
                    </form>
                </x-card>
            </div>
        @endif

        @if($tab === 'texts')
            <x-card title="متون رابط کاربری">
                <form method="POST" action="{{ route('admin.settings.texts') }}" class="space-y-4 max-w-2xl">
                    @csrf @method('PUT')
                    <x-form.input label="پیام خوش‌آمدگویی داشبورد" name="welcome_message" :value="$settings['texts']['welcome_message'] ?? ''" required />
                    <x-form.input label="سلام داشبورد (قبل از نام)" name="dashboard_greeting" :value="$settings['texts']['dashboard_greeting'] ?? ''" required />
                    <x-form.input label="عنوان صفحه ورود ادمین" name="login_title" :value="$settings['texts']['login_title'] ?? ''" required />
                    <x-form.input label="زیرعنوان ورود ادمین" name="login_subtitle" :value="$settings['texts']['login_subtitle'] ?? ''" required />
                    <x-form.input label="متن فوتر" name="footer_text" :value="$settings['texts']['footer_text'] ?? ''" />
                    <x-form.input label="عنوان بخش استخدام" name="recruitment_title" :value="$settings['texts']['recruitment_title'] ?? ''" required />
                    <x-form.input label="زیرعنوان استخدام" name="recruitment_subtitle" :value="$settings['texts']['recruitment_subtitle'] ?? ''" required />
                    <x-form.input label="عنوان پورتال پرسنل" name="portal_title" :value="$settings['texts']['portal_title'] ?? ''" required />
                    <x-form.input label="زیرعنوان پورتال پرسنل" name="portal_subtitle" :value="$settings['texts']['portal_subtitle'] ?? ''" required />
                    <x-button type="submit" variant="primary">ذخیره متون</x-button>
                </form>
                <p class="text-xs text-slate-500 mt-4">متون پیامک در تب «پنل پیامکی» مدیریت می‌شوند.</p>
            </x-card>
        @endif
    </div>
</x-layouts.app>
