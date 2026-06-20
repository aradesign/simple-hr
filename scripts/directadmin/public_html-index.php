<?php

/**
 * public_html/index.php — مسیر Laravel در پوشه data (هم‌سطح public_html)
 * ساختار:
 *   /home/user/domains/example.com/data/      ← اپلیکیشن
 *   /home/user/domains/example.com/public_html/ ← وب
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = dirname(__DIR__).'/data';

if (! is_dir($laravelRoot)) {
    http_response_code(500);
    exit('Laravel root not found. Expected folder: '.htmlspecialchars($laravelRoot));
}

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

$app = require_once $laravelRoot.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
