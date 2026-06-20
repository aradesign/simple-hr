<?php

/**
 * راه‌اندازی یک‌بار — بعد از آپلود و تنظیم data/.env
 * https://دامنه/setup.php?key=YOUR_KEY
 * ⚠️ بعد از موفقیت حذف کنید.
 */

declare(strict_types=1);

const SETUP_KEY = 'CHANGE_ME_BEFORE_UPLOAD';

header('Content-Type: text/html; charset=utf-8');

$steps = [];
$failed = false;

function step(string $label, callable $fn): void
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

function step_warn(string $label, callable $fn): void
{
    global $steps;
    try {
        $fn();
        $steps[] = ['ok', $label];
    } catch (Throwable $e) {
        $steps[] = ['warn', $label.': '.$e->getMessage()];
    }
}

function link_public_storage(string $laravelRoot): void
{
    $publicHtml = dirname($laravelRoot).'/public_html';
    $target = $laravelRoot.'/storage/app/public';
    $link = $publicHtml.'/storage';

    if (! is_dir($publicHtml)) {
        throw new RuntimeException('public_html یافت نشد: '.$publicHtml);
    }

    if (! is_dir($target) && ! mkdir($target, 0775, true)) {
        throw new RuntimeException('ساخت storage/app/public ممکن نیست');
    }

    if (is_link($link) && readlink($link) !== false) {
        return;
    }

    if (file_exists($link)) {
        if (is_dir($link)) {
            $items = array_diff(scandir($link) ?: [], ['.', '..', '.gitignore']);
            if ($items !== []) {
                throw new RuntimeException('public_html/storage از قبل فایل دارد — خالی کنید');
            }
            @unlink($link.'/.gitignore');
            @rmdir($link);
        } else {
            @unlink($link);
        }
    }

    $relativeTarget = '../data/storage/app/public';
    if (@symlink($relativeTarget, $link)) {
        return;
    }

    if (@symlink($target, $link)) {
        return;
    }

    throw new RuntimeException(
        'symlink خودکار نشد. در File Manager: public_html/storage → ../data/storage/app/public'
    );
}

function artisan(string $root, string $command): string
{
    $php = PHP_BINARY ?: 'php';
    $cmd = 'cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan '.$command.' 2>&1';
    $output = shell_exec($cmd);

    if ($output === null || $output === '') {
        throw new RuntimeException('artisan اجرا نشد. shell_exec غیرفعال است؟');
    }

    if (preg_match('/\b(ERROR|Exception|SQLSTATE)\b/i', $output) && ! preg_match('/Nothing to migrate/i', $output)) {
        if (! preg_match('/Application key set successfully/i', $output)
            && ! preg_match('/INFO\s+Running migrations/i', $output)
            && ! preg_match('/Seeding database/i', $output)) {
            // allow warnings in output; fail on obvious errors after key:generate
        }
    }

    if (str_contains($output, 'SQLSTATE') || str_contains($output, 'Parse error') || str_contains($output, 'Fatal error')) {
        throw new RuntimeException(trim($output));
    }

    return trim($output);
}

try {
    if (($_GET['key'] ?? '') !== SETUP_KEY) {
        http_response_code(403);
        exit('Forbidden — کلید setup اشتباه است.');
    }

    $laravelRoot = dirname(__DIR__).'/data';

    step('مسیر data', function () use ($laravelRoot) {
        if (! is_dir($laravelRoot)) {
            throw new RuntimeException('پوشه data یافت نشد: '.$laravelRoot);
        }
        if (! is_file($laravelRoot.'/vendor/autoload.php')) {
            throw new RuntimeException('vendor/autoload.php نیست — data را کامل آپلود کنید.');
        }
    });

    step('PHP '.PHP_VERSION, function () {
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            throw new RuntimeException('PHP 8.1+ لازم است. از DirectAdmin → PHP Version ارتقا دهید.');
        }
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'] as $ext) {
            if (! extension_loaded($ext)) {
                throw new RuntimeException("افزونه PHP فعال نیست: {$ext}");
            }
        }
    });

    step('فایل .env', function () use ($laravelRoot) {
        if (! is_file($laravelRoot.'/.env')) {
            throw new RuntimeException('data/.env وجود ندارد. .env.hosting.example را کپی و rename به .env کنید.');
        }
        $env = file_get_contents($laravelRoot.'/.env');
        if (str_contains($env, 'YOUR_CPANEL_DB')) {
            throw new RuntimeException('DB_DATABASE / DB_USERNAME / DB_PASSWORD را در .env پر کنید.');
        }
    });

    step('دسترسی storage', function () use ($laravelRoot) {
        foreach (['storage', 'storage/logs', 'storage/framework', 'storage/framework/cache', 'storage/app', 'storage/app/public', 'bootstrap/cache'] as $dir) {
            $path = $laravelRoot.'/'.$dir;
            if (! is_dir($path) && ! mkdir($path, 0775, true)) {
                throw new RuntimeException("ساخت پوشه ممکن نیست: {$dir}");
            }
            if (! is_writable($path)) {
                @chmod($path, 0775);
            }
            if (! is_writable($path)) {
                throw new RuntimeException("پوشه قابل نوشتن نیست: {$dir} — chmod 775");
            }
        }
    });

    step('پاکسازی cache', function () use ($laravelRoot) {
        foreach (glob($laravelRoot.'/bootstrap/cache/*.php') ?: [] as $file) {
            @unlink($file);
        }
    });

    step('APP_KEY', function () use ($laravelRoot) {
        artisan($laravelRoot, 'key:generate --force');
    });

    step('migrate', function () use ($laravelRoot) {
        artisan($laravelRoot, 'migrate --force');
    });

    step('seed', function () use ($laravelRoot) {
        artisan($laravelRoot, 'db:seed --force');
    });

    step_warn('storage:link (public_html/storage)', function () use ($laravelRoot) {
        link_public_storage($laravelRoot);
    });

    step('cache', function () use ($laravelRoot) {
        @artisan($laravelRoot, 'config:cache');
        @artisan($laravelRoot, 'route:cache');
        @artisan($laravelRoot, 'view:cache');
    });
} catch (Throwable $e) {
    $steps[] = ['err', 'خطای کلی: '.$e->getMessage()];
    $failed = true;
}

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>Setup Simple HR</title></head>
<body style="font-family:tahoma;padding:2rem;line-height:1.8">
<h1>راه‌اندازی Simple HR</h1>
<ul>
<?php foreach ($steps as [$status, $msg]): ?>
<li style="color:<?= match ($status) { 'ok' => 'green', 'warn' => '#b45309', default => 'red' } ?>"><?= nl2br(htmlspecialchars($msg)) ?></li>
<?php endforeach; ?>
</ul>
<?php
$coreOk = ! $failed;
$hasWarn = (bool) array_filter($steps, fn ($s) => $s[0] === 'warn');
?>
<?php if ($coreOk && count($steps) > 0): ?>
<p><strong><?= $hasWarn ? 'نصب انجام شد (یک هشدار)' : 'موفق' ?>.</strong> <a href="/admin/login">ورود به پنل</a></p>
<p>admin@example.com / password — فوراً رمز را عوض کنید.</p>
<p style="color:red"><strong>setup.php را حذف کنید.</strong></p>
<?php elseif ($failed): ?>
<p>اگر خطا مبهم بود، اول <a href="/check.php">check.php</a> را باز کنید.</p>
<?php endif; ?>
</body></html>
