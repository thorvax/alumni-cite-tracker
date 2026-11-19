<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::inertia('/privacy', 'privacy')->name('privacy');

Route::middleware(['auth', 'verified'])->group(function () {});

require __DIR__.'/settings.php';
require __DIR__.'/survey.php';
require __DIR__.'/alumni.php';
require __DIR__.'/admin.php';
