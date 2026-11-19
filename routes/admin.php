<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GraduateController;
use App\Http\Controllers\GraduateImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Bulk upload. Declared before the resource so "import" is not read as a graduate id.
    Route::controller(GraduateImportController::class)->prefix('graduates/import')->name('graduates.import')->group(function () {
        Route::get('/', 'index')->name('');
        Route::get('template', 'template')->name('.template');
        Route::post('preview', 'preview')->name('.preview');
        Route::post('confirm', 'confirm')->name('.confirm');
        Route::delete('/', 'cancel')->name('.cancel');
        Route::post('resend-failed', 'resendFailed')->name('.resend-failed');
        Route::post('emails', 'saveEmails')->name('.emails');
        Route::post('remind', 'remind')->name('.remind');
        Route::patch('schedule', 'updateSchedule')->name('.schedule');
        Route::post('{graduate}/resend', 'resend')->name('.resend');
        Route::patch('{graduate}/email', 'saveEmail')->name('.email');
    });

    Route::resource('graduates', GraduateController::class)
        ->except(['destroy']);

    Route::get('search', SearchController::class)->name('search');
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('settings/system', [SettingsController::class, 'show'])->name('admin.settings');
    Route::patch('settings/system/account', [SettingsController::class, 'updateAccount'])->name('admin.settings.account');
    Route::patch('settings/system', [SettingsController::class, 'updateSystem'])->name('admin.settings.system');
});
