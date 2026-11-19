<?php

use App\Http\Controllers\Alumni\ActivationController;
use App\Http\Controllers\Alumni\EditSurveyController;
use App\Http\Controllers\Alumni\ForgotPasswordController;
use App\Http\Controllers\Alumni\LoginController;
use App\Http\Controllers\Alumni\MySurveyController;
use App\Http\Controllers\Alumni\SetPasswordController;
use App\Http\Controllers\Alumni\VerifyCodeController;
use Illuminate\Support\Facades\Route;

/*
 * Alumni (graduates) accounts. Separate from the admin login: these use the
 * "graduate" guard. The personal email link (/survey/start/{token}) is in survey.php.
 */
Route::prefix('alumni')->name('alumni.')->group(function () {
    // Returning users. The login is also rate limited inside the controller.
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Forgot password, page 1 (then the same code + password pages as activation).
    Route::get('forgot-password', [ForgotPasswordController::class, 'show'])->name('forgot.show');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('forgot.store');

    // Activation page 1: Student ID Number + Last Name.
    Route::get('activate', [ActivationController::class, 'show'])->name('activate.show');
    Route::post('activate', [ActivationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('activate.store');

    // Page 2 (activation and password reset): the code from the email.
    Route::controller(VerifyCodeController::class)->prefix('verify-code')->name('code.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
        Route::post('resend', 'resend')->middleware('throttle:5,1')->name('resend');
    });

    // Page 3: choose a password.
    Route::controller(SetPasswordController::class)->prefix('set-password')->name('password.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/', 'store')->name('store');
    });

    Route::middleware('auth:graduate')->group(function () {
        Route::get('my-survey', MySurveyController::class)->name('my-survey');
        Route::post('my-survey/edit', EditSurveyController::class)->name('survey.edit');
    });
});
