<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GraduateController;
use App\Http\Controllers\GraduateHistoryController;
use App\Http\Controllers\GraduateImportController;
use App\Http\Controllers\GraduateRemindersController;
use App\Http\Controllers\GraduateUpdatesController;
use App\Http\Controllers\RemindersController;
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

    // Invitations: email status of uploaded graduates (actions stay under graduates/import).
    Route::get('graduates/invitations', [GraduateImportController::class, 'invitations'])->name('graduates.invitations');

    // Reminders: survey reminders (not answered yet) and update reminders.
    Route::redirect('reminders', 'reminders/survey');
    Route::get('reminders/survey', [RemindersController::class, 'survey'])->name('reminders.survey');
    Route::get('reminders/update', [RemindersController::class, 'update'])->name('reminders.update');

    // Declared before the resource so "updates" is not read as a graduate id.
    Route::get('graduates/updates', GraduateUpdatesController::class)->name('graduates.updates');
    Route::get('graduates/reminders', GraduateRemindersController::class)->name('graduates.reminders');

    Route::resource('graduates', GraduateController::class)
        ->except(['destroy']);
    Route::post('graduates/{graduate}/update-reminder', [GraduateController::class, 'sendUpdateReminder'])
        ->name('graduates.update-reminder');
    Route::get('graduates/{graduate}/history', GraduateHistoryController::class)->name('graduates.history');

    Route::get('search', SearchController::class)->name('search');
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('settings/system', [SettingsController::class, 'show'])->name('admin.settings');
    Route::patch('settings/system/account', [SettingsController::class, 'updateAccount'])->name('admin.settings.account');
    Route::patch('settings/system', [SettingsController::class, 'updateSystem'])->name('admin.settings.system');
    Route::patch('settings/system/update-reminders', [SettingsController::class, 'updateReminders'])->name('admin.settings.update-reminders');
    Route::post('settings/system/update-reminders/send', [SettingsController::class, 'sendUpdateReminders'])->name('admin.settings.update-reminders.send');
});
