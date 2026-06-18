<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response('OK', 200));

Route::get('/', function () {
    if (Auth::check() && Auth::user()->hasHrAccess()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('recruitment.login');
});

require __DIR__.'/admin.php';
require __DIR__.'/recruitment.php';
require __DIR__.'/portal.php';
