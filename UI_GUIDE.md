# HR Portal V2 — راهنمای رابط کاربری

## ۱. اصول کلی

| اصل | الزام |
|-----|-------|
| زبان | کاملاً فارسی |
| جهت | RTL (`dir="rtl"`) |
| رویکرد | Mobile First |
| تم | Light + Dark Mode |
| تاریخ | شمسی در تمام UI |
| وابستگی | بدون CDN — همه asset محلی |

---

## ۲. Design Tokens (Tailwind)

### رنگ‌ها

```css
/* Light Mode */
--color-primary: #2563eb;      /* blue-600 */
--color-primary-dark: #1d4ed8;
--color-secondary: #64748b;    /* slate-500 */
--color-success: #16a34a;      /* green-600 */
--color-warning: #d97706;      /* amber-600 */
--color-danger: #dc2626;       /* red-600 */
--color-surface: #ffffff;
--color-background: #f8fafc;   /* slate-50 */
--color-border: #e2e8f0;       /* slate-200 */
--color-text: #0f172a;         /* slate-900 */
--color-text-muted: #64748b;

/* Dark Mode */
--color-surface-dark: #1e293b;     /* slate-800 */
--color-background-dark: #0f172a;  /* slate-900 */
--color-border-dark: #334155;
--color-text-dark: #f1f5f9;
```

### تایپوگرافی

- **فونت:** Vazirmatn (local, weights: 400, 500, 600, 700)
- **سایز پایه:** 14px (mobile), 16px (desktop)
- **عناوین:** font-semibold تا font-bold

### فاصله‌گذاری

- Padding کارت: `p-4` (mobile) → `p-6` (desktop)
- Gap بین بخش‌ها: `gap-4` → `gap-6`
- Border radius: `rounded-lg` (8px) برای کارت‌ها، `rounded-xl` (12px) برای modal

---

## ۳. Layout Structure

```
┌──────────────────────────────────────────────────┐
│  Header (fixed top)                              │
│  [Logo] [Search] [Notifications] [Theme] [User]│
├──────────┬───────────────────────────────────────┤
│          │  Hero Banner (optional, dashboard)    │
│ Sidebar  ├───────────────────────────────────────┤
│ (fixed   │                                       │
│  right)  │  Main Content Area                    │
│          │                                       │
│          │                                       │
└──────────┴───────────────────────────────────────┘
```

### Sidebar (راست — RTL)

- عرض: `w-64` (desktop), drawer در mobile
- پس‌زمینه: `bg-white dark:bg-slate-800`
- آیتم فعال: `bg-blue-50 dark:bg-blue-900/30 text-blue-600`
- آیکون + متن فارسی

### Header

- ارتفاع: `h-16`
- سایه ملایم: `shadow-sm`
- دکمه همبرگر برای mobile sidebar

### Hero Banner

- گرادیان آبی: `from-blue-600 to-blue-800`
- متن خوش‌آمدگویی + تاریخ شمسی امروز
- فقط در داشبورد

---

## ۴. Blade Components

### `<x-app-layout>`

Layout اصلی با sidebar + header + slot

```blade
<x-app-layout title="داشبورد">
    <x-slot:hero>...</x-slot:hero>
    {{ $slot }}
</x-app-layout>
```

### `<x-sidebar>`

منوی ناوبری با آیتم‌های:
- داشبورد
- پرسنل
- درخواست‌های استخدام
- مصاحبه‌ها
- تقویم HR
- اسناد
- دپارتمان‌ها
- کاربران
- تنظیمات فرم
- گزارش فعالیت

### `<x-card>`

```blade
<x-card title="عنوان" :actions="$actions">
    محتوا
</x-card>
```

### `<x-stat-card>`

کارت آماری داشبورد با آیکون، عدد، برچسب

### `<x-data-table>`

جدول responsive با:
- Header sortable
- Pagination
- Empty state
- Mobile: card view

### `<x-badge>`

وضعیت‌ها با رنگ:

| وضعیت | رنگ |
|-------|-----|
| applicant | `bg-gray-100 text-gray-700` |
| interviewed | `bg-blue-100 text-blue-700` |
| accepted | `bg-green-100 text-green-700` |
| employee | `bg-emerald-100 text-emerald-700` |
| former_employee | `bg-slate-100 text-slate-600` |
| rejected | `bg-red-100 text-red-700` |
| pending | `bg-amber-100 text-amber-700` |

### `<x-modal>`

AlpineJS: `x-data="{ open: false }"` + transition

### `<x-drawer>`

از سمت راست (RTL) باز می‌شود

### `<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`

فیلدهای فرم یکپارچه با label فارسی، error message، RTL

### `<x-jalali-date>`

نمایش تاریخ شمسی — input با date picker شمسی

### `<x-button>`

Variants: `primary`, `secondary`, `danger`, `ghost`
Sizes: `sm`, `md`, `lg`

### `<x-alert>`

انواع: success, warning, error, info

### `<x-empty-state>`

حالت خالی با آیکون و CTA

### `<x-pagination>`

صفحه‌بندی فارسی

---

## ۵. صفحات اصلی

### ۵.۱ داشبورد HR (`/admin/dashboard`)

**Hero:** «سلام، {نام} — {تاریخ شمسی}»

**Stat Cards (grid 2×3 mobile, 3×2 desktop):**
1. متقاضیان فعال
2. مصاحبه‌های امروز
3. تولدهای امروز
4. قراردادهای در آستانه انقضا
5. پرسنل فعال
6. درخواست‌های جدید

**بخش‌های پایین:**
- وظایف ارجاع شده (جدول کوتاه)
- مصاحبه‌های امروز (لیست)
- درخواست‌های جدید (لیست)

### ۵.۲ لیست پرسنل (`/admin/persons`)

- فیلتر: وضعیت lifecycle، دپارتمان، جستجو
- جدول: نام، موبایل، وضعیت، دپارتمان، عملیات
- دکمه: افزودن پرسنل

### ۵.۳ جزئیات پرونده Person (`/admin/persons/{id}`)

Tab-based:
1. اطلاعات پایه
2. تماس و آدرس
3. خانواده
4. تحصیلات
5. سوابق شغلی
6. درخواست‌های استخدام
7. مصاحبه‌ها
8. اسناد
9. سوابق همکاری
10. فعالیت (audit)

### ۵.۴ درخواست‌های استخدام (`/admin/applications`)

- فیلتر پیشرفته: وضعیت، تاریخ، ارجاع‌شده به
- Export: PDF, CSV
- Bulk actions

### ۵.۵ مصاحبه‌ها (`/admin/interviews`)

- تقویم + لیست
- ایجاد مصاحبه (modal)
- ثبت نتیجه

### ۵.۶ تقویم HR (`/admin/calendar`)

- نمای ماهانه (شمسی)
- رنگ‌بندی بر اساس event_type
- کلیک → جزئیات رویداد

### ۵.۷ Recruitment Public (`/recruitment`)

1. ورود OTP
2. فرم چندمرحله‌ای (stepper)
3. پیگیری درخواست

### ۵.۸ Employee Portal (`/portal`)

1. ورود OTP
2. پروفایل
3. تکمیل اطلاعات
4. اسناد
5. اعلان‌ها

---

## ۶. Dark Mode

```html
<html dir="rtl" lang="fa" x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
      :class="{ 'dark': dark }">
```

Toggle در Header:
```javascript
dark = !dark;
localStorage.setItem('theme', dark ? 'dark' : 'light');
```

---

## ۷. Date Picker شمسی

کامپوننت AlpineJS محلی:
- نمایش: `۱۴۰۳/۰۹/۲۵`
- انتخاب از grid ماه شمسی
- مقدار ارسالی به سرور: تبدیل به میلادی در Form Request
- نمایش در UI: همیشه شمسی via `morilog/jalali`

---

## ۸. Responsive Breakpoints

| Breakpoint | عرض | رفتار |
|------------|-----|-------|
| default | < 640px | Sidebar drawer, cards stack |
| sm | 640px+ | 2-column grids |
| md | 768px+ | Sidebar visible |
| lg | 1024px+ | Full layout |
| xl | 1280px+ | Wider content |

---

## ۹. Accessibility

- `aria-label` روی دکمه‌های آیکونی
- Focus ring: `focus:ring-2 focus:ring-blue-500`
- Contrast ratio مناسب در dark mode
- `lang="fa"` روی html

---

## ۱۰. فایل‌های Asset محلی

```
resources/
├── css/app.css          # Tailwind imports
├── js/app.js            # Alpine + utilities
├── fonts/
│   └── Vazirmatn/       # woff2 files
└── icons/               # SVG sprites
```

**ممنوع:** Google Fonts, CDN JS/CSS, unpkg, jsdelivr

---

## ۱۱. الگوی صفحه نمونه

```blade
<x-app-layout title="پرسنل">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">مدیریت پرسنل</h1>
            <x-button href="{{ route('admin.persons.create') }}">افزودن پرسنل</x-button>
        </div>

        <x-card>
            {{-- filters + table --}}
        </x-card>
    </div>
</x-app-layout>
```

---

## ۱۲. انیمیشن‌ها

- Modal/Drawer: `transition` با `opacity` + `transform`
- Sidebar mobile: slide from right
- Hover states: `transition-colors duration-150`
- بدون انیمیشن سنگین — performance first
