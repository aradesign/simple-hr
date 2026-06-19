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

## انتشار روی GitHub

مخزن git آماده است. برای push:

```bash
# روی github.com یک مخزن خالی بسازید (بدون README)
git remote add origin git@github.com:aradesign/simple-hr.git
git push -u origin main
```

یا با HTTPS:

```bash
git remote add origin https://github.com/aradesign/simple-hr.git
git push -u origin main
```

قبل از push، assets را بسازید (روی VPS هم لازم است):

```bash
npm ci && npm run build
```

فایل `public/build` در gitignore است — یا آن را از ignore خارج کنید و commit کنید، یا روی سرور `npm run build` بزنید.


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
