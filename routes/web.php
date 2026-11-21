<?php

use App\Http\Controllers\PublicAnnouncementController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::inertia('/privacy', 'privacy')->name('privacy');

// Public announcements (no login). Announcements for selected alumni are never listed here.
Route::get('/announcements', [PublicAnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}', [PublicAnnouncementController::class, 'show'])->name('announcements.show');

Route::middleware(['auth', 'verified'])->group(function () {});

require __DIR__.'/settings.php';
require __DIR__.'/survey.php';
require __DIR__.'/alumni.php';
require __DIR__.'/admin.php';
