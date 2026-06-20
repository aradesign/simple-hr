<?php

/**
 * تشخیص مشکل نصب — بدون Laravel
 * https://دامنه/check.php
 * ⚠️ بعد از نصب حذف کنید.
 */

header('Content-Type: text/html; charset=utf-8');

$root = dirname(__DIR__);
$data = $root.'/data';
$checks = [];

function ok(string $m): array { return ['ok', $m]; }
function bad(string $m): array { return ['err', $m]; }

$checks[] = ok('PHP '.PHP_VERSION);
$checks[] = version_compare(PHP_VERSION, '8.1.0', '>=')
    ? ok('نسخه PHP مناسب')
    : bad('PHP 8.1+ لازم است — DirectAdmin → Select PHP Version');

foreach (['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'curl', 'intl', 'zip', 'gd'] as $ext) {
    $checks[] = extension_loaded($ext) ? ok("ext: {$ext}") : bad("ext فعال نیست: {$ext}");
}

$checks[] = is_dir($data)
    ? ok('پوشه data: '.$data)
    : bad('پوشه data نیست. باید هم‌سطح public_html باشد: '.$data);

$checks[] = is_file($data.'/vendor/autoload.php')
    ? ok('vendor موجود')
    : bad('vendor/autoload.php نیست');

$checks[] = is_file($data.'/.env')
    ? ok('data/.env موجود')
    : bad('data/.env نیست — از .env.hosting.example کopy → rename به .env');

if (is_file($data.'/.env')) {
    $env = file_get_contents($data.'/.env');
    $checks[] = str_contains($env, 'YOUR_CPANEL_DB')
        ? bad('.env هنوز placeholder دارد — DB را پر کنید')
        : ok('.env تنظیم شده');
    preg_match('/^DB_DATABASE=(.+)$/m', $env, $m);
    $checks[] = ok('DB_DATABASE='.trim($m[1] ?? '?'));
}

foreach (['storage', 'storage/logs', 'bootstrap/cache'] as $rel) {
    $p = $data.'/'.$rel;
    if (! is_dir($p)) {
        $checks[] = bad("پوشه نیست: {$rel}");
    } elseif (! is_writable($p)) {
        $checks[] = bad("قابل نوشتن نیست: {$rel} — chmod 775");
    } else {
        $checks[] = ok("writable: {$rel}");
    }
}

$checks[] = function_exists('shell_exec') && ! in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true)
    ? ok('shell_exec فعال (برای setup.php)')
    : bad('shell_exec غیرفعال — migrate را از SSH اجرا کنید');

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>Check Simple HR</title></head>
<body style="font-family:tahoma;padding:2rem">
<h1>بررسی نصب Simple HR</h1>
<ul>
<?php foreach ($checks as [$s, $m]): ?>
<li style="color:<?= $s === 'ok' ? 'green' : 'red' ?>"><?= htmlspecialchars($m) ?></li>
<?php endforeach; ?>
</ul>
<p>مسیر public_html: <?= htmlspecialchars($root) ?></p>
<p>مسیر data: <?= htmlspecialchars($data) ?></p>
</body></html>
