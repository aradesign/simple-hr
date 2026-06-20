<?php

/**
 * راه‌اندازی یک‌بار — بعد از آپلود و تنظیم .env
 * آدرس: https://دامنه/setup.php?key=SETUP_KEY_HERE
 * ⚠️ بعد از موفقیت حتماً این فایل را حذف کنید.
 */

declare(strict_types=1);

const SETUP_KEY = 'CHANGE_ME_BEFORE_UPLOAD';

header('Content-Type: text/html; charset=utf-8');

if (($_GET['key'] ?? '') !== SETUP_KEY) {
    http_response_code(403);
    exit('Forbidden');
}

$laravelRoot = dirname(__DIR__).'/data';
$autoload = $laravelRoot.'/vendor/autoload.php';

if (! is_file($autoload)) {
    exit('vendor یافت نشد. پوشه data را کامل آپلود کنید.');
}

require $autoload;

$app = require $laravelRoot.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$steps = [];
$failed = false;

function run_step(string $label, callable $fn): void
{
    global $steps, $failed;
    try {
        $fn();
        $steps[] = ['ok', $label];
    } catch (Throwable $e) {
        $steps[] = ['err', $label.': '.$e->getMessage()];
        $failed = true;
    }
}

run_step('بررسی .env', function () use ($laravelRoot) {
    if (! is_file($laravelRoot.'/.env')) {
        throw new RuntimeException('فایل data/.env وجود ندارد. از .env.hosting.example کپی کنید و MySQL را پر کنید.');
    }
    if (str_contains(file_get_contents($laravelRoot.'/.env'), 'YOUR_CPANEL_DB')) {
        throw new RuntimeException('DB_DATABASE و DB_USERNAME و DB_PASSWORD را در .env تنظیم کنید.');
    }
});

run_step('APP_KEY', function () use ($kernel) {
    $kernel->call('key:generate', ['--force' => true]);
});

run_step('migrate', function () use ($kernel) {
    $kernel->call('migrate', ['--force' => true]);
});

run_step('seed', function () use ($kernel) {
    $kernel->call('db:seed', ['--force' => true]);
});

run_step('storage:link', function () use ($kernel) {
    $kernel->call('storage:link');
});

run_step('cache', function () use ($kernel) {
    $kernel->call('config:cache');
    $kernel->call('route:cache');
    $kernel->call('view:cache');
});

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>Setup</title></head>
<body style="font-family:tahoma;padding:2rem">
<h1>راه‌اندازی Simple HR</h1>
<ul>
<?php foreach ($steps as [$status, $msg]): ?>
<li style="color:<?= $status === 'ok' ? 'green' : 'red' ?>"><?= htmlspecialchars($msg) ?></li>
<?php endforeach; ?>
</ul>
<?php if (! $failed): ?>
<p><strong>موفق.</strong> ورود: <code>/admin/login</code> — admin@example.com / password</p>
<p style="color:red">فوراً setup.php را حذف کنید و رمز ادمین را عوض کنید.</p>
<?php else: ?>
<p style="color:red">خطا — لاگ: data/storage/logs/laravel.log</p>
<?php endif; ?>
</body></html>
