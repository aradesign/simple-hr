<?php

namespace App\Support;

use App\Services\Recruitment\ApplicationFormSchemaService;

final class PersonnelCsvColumnMap
{
    /** @var array<string, list<string>> */
    private const FORM_ALIASES = [
        'first_name' => ['first_name', 'firstname', 'نام'],
        'last_name' => ['last_name', 'lastname', 'نام_خانوادگی'],
        'father_name' => ['fathername', 'father_name', 'نام_پدر'],
        'mother_name' => ['mothername', 'mother_name', 'نام_مادر'],
        'birth_date' => ['birthdate', 'birth_date', 'تاریخ_تولد'],
        'national_id' => ['nationalcode', 'national_code', 'nationalid', 'کد_ملی'],
        'id_card_number' => ['identitycertificate', 'id_card_number', 'شماره_شناسنامه'],
        'profile_photo' => ['personal_photo', 'profile_photo', 'ums_avatar_url', 'تصویر_پروفایل'],
        'gender' => ['sex', 'gender', 'جنسیت'],
        'marital_status' => ['marriage', 'marital_status', 'وضعیت_تأهل'],
        'mobile' => ['mobile_user', 'mobile', 'تلفن_همراه'],
        'home_phone' => ['user_phone', 'home_phone', 'تلفن_محل_سکونت'],
        'address' => ['address', 'آدرس_محل_سکونت', 'آدرس'],
        'education_level' => ['education', 'education_level', 'میزان_تحصیلات'],
        'preferred_department' => ['position', 'preferred_department', 'بخش_فعالیت'],
        'entry_date' => ['register_date', 'user_registered', 'entry_date', 'تاریخ_ورودی'],
    ];

    /** @var array<string, list<string>> */
    private const META_ALIASES = [
        'source_user_id' => ['id'],
        'employment_start_date' => ['workstartdate', 'workstart_date', 'workstars_date'],
        'user_email' => ['user_email'],
        'display_name' => ['display_name'],
        'user_login' => ['user_login'],
    ];

    /** @var list<string> */
    private const IGNORED_ALIASES = [
        'user_nicename',
        'user_url',
        'user_status',
        'admin_color',
        'closedpostboxes_dashboard',
        'code_activation_login_user',
        'comment_shortcuts',
        'date_box_1725119077',
        'description',
        'dismissed_wp_pointers',
        'duplicator_dismissed_admin_notices',
        'duplicator_email_subscribed',
        'duplicator_packages_bottom_bar_dismissed',
        'elementor_admin_notices',
        'elementor_introduction',
        'file_1725116868',
        'is_activated_email',
        'locale',
        'managenav_menuscolumnshidden',
        'managepluginscolumnshidden',
        'manageuserscolumnshidden',
        'metaboxhidden_dashboard',
        'metaboxhidden_nav_menus',
        'nationalidentityimage',
        'nickname',
        'rich_editing',
        'role',
        'show_admin_bar_front',
        'show_welcome_panel',
        'source_user_id',
        'syntax_highlighting',
        'ums_country_code',
        'ums_is_activated_mobile',
        'ums_user_status',
        'use_ssl',
        'users_per_page',
        'wfls_last_login',
        'wp_dashboard_quick_press_last_post_id',
        'wp_elementor_enable_ai',
        'wp_media_library_mode',
        'wp_user_level',
        'wp_wpse_sheet_sessions',
        'wpse_has_saved_sheet',
        'ref',
    ];

    /** @var array<string, string> */
    private array $labelIndex = [];

    public function __construct(
        private readonly ApplicationFormSchemaService $schemaService,
    ) {
        $this->labelIndex = $this->buildLabelIndex();
    }

    public function resolve(string $header): ?string
    {
        if (in_array(trim($header), ['#REF!', '#ref!'], true)) {
            return null;
        }

        $normalized = $this->normalize($header);

        if ($normalized === '' || in_array($normalized, self::IGNORED_ALIASES, true)) {
            return null;
        }

        foreach (self::FORM_ALIASES as $formKey => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $formKey;
            }
        }

        if (isset($this->labelIndex[$normalized])) {
            return $this->labelIndex[$normalized];
        }

        foreach (self::META_ALIASES as $metaKey => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return '_meta:'.$metaKey;
            }
        }

        return '_extra:'.$header;
    }

    /** @return array<string, string> */
    private function buildLabelIndex(): array
    {
        $index = [];

        foreach ($this->schemaService->getAllFields() as $field) {
            $index[$this->normalize($field->field_key)] = $field->field_key;
            $index[$this->normalize($field->label)] = $field->field_key;
        }

        return $index;
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^\x{FEFF}/u', '', $value) ?? $value;
        $value = PersianDigits::toEnglish($value);
        $value = str_replace(['-', ' ', '‌'], '_', $value);

        return mb_strtolower($value);
    }
}
