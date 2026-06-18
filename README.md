# Leili HR

سامانه منابع انسانی و استخدام — Laravel + Tailwind + Alpine.js

## نیازمندی‌ها

- PHP 8.1+
- Composer
- Node.js 20+ (برای build فرانت‌اند)
- SQLite (توسعه) یا MySQL/MariaDB (production)

## راه‌اندازی لوکال

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

ورود پیش‌فرض پس از seed (فقط محیط توسعه):

- ایمیل: `admin@example.com`
- رمز: `password`

## تست

```bash
php artisan test
```

## استقرار روی VPS

راهنمای کامل فارسی: [`docs/hosting-vps-ubuntu.html`](docs/hosting-vps-ubuntu.html) — فایل را در مرورگر باز کنید.

## داده‌های حساس

این مخزن عمداً **بدون داده واقعی پرسنل و درخواست‌ها** منتشر می‌شود. import از پنل ادمین انجام دهید.

## ساختار مهم

| مسیر | توضیح |
|------|--------|
| `app/Services/Recruitment/` | import/export درخواست‌ها |
| `app/Services/Person/` | import پرسنل |
| `docs/` | راهنمای استقرار |
| `tests/fixtures/` | نمونه CSV **ساختگی** برای تست |
