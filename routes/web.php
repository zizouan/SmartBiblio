<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'BiblioSmart API',
        'status' => 'ok',
        'version' => 'v1',
        'base_url' => url('/api/v1'),
        'auth_login' => url('/api/v1/auth/login'),
    ]);
});

Route::get('/reset-password/{token}', function (string $token) {
    return response()->json(['token' => $token]);
})->name('password.reset');
