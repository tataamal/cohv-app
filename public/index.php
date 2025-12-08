<?php

use Illuminate\Http\Request;

// 1. Definisikan Base Path & Public Path
// Ini penting agar FrankenPHP tahu posisi root folder project Anda
$_SERVER['APP_BASE_PATH'] = $_ENV['APP_BASE_PATH'] ?? $_SERVER['APP_BASE_PATH'] ?? __DIR__.'/..';
$_SERVER['APP_PUBLIC_PATH'] = $_ENV['APP_PUBLIC_PATH'] ?? $_SERVER['APP_BASE_PATH'] ?? __DIR__;

// 2. Load Auto Loader (Standar)
require __DIR__.'/../vendor/autoload.php';

// 3. Bootstrapping Aplikasi (Standar)
$app = require __DIR__.'/../bootstrap/app.php';

// 4. Panggil Worker FrankenPHP bawaan Octane
// File ini yang melakukan "Magic" looping request agar aplikasi tetap hidup di RAM
require __DIR__.'/../vendor/laravel/octane/bin/frankenphp-worker.php';
