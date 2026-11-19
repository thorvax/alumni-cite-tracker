<?php

use App\Http\Controllers\Alumni\ActivationController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::prefix('survey')->name('survey.')->group(function () {
    // Personal link from the invitation email: starts account activation.
    // Public: it is the only way to create an alumni account.
    Route::get('start/{token}', [ActivationController::class, 'link'])
        ->where('token', '[A-Za-z0-9]{48}')
        ->name('invite');

    // The survey itself needs the alumni login, so every answer belongs to one
    // uploaded graduate and submitting again updates that same record.
    Route::controller(SurveyController::class)->middleware('auth:graduate')->group(function () {
        // The letter + Data Privacy Notice; ticking "I agree" opens the steps.
        Route::get('/', 'start')->name('start');
        Route::post('/', 'consent')->name('consent');

        Route::get('{step}', 'show')
            ->whereIn('step', SurveyController::STEPS)
            ->name('show');

        Route::post('{step}', 'store')
            ->whereIn('step', SurveyController::STEPS)
            ->name('store');
    });
});
