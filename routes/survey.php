<?php

use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::controller(SurveyController::class)
    ->prefix('survey')
    ->name('survey.')
    ->group(function () {
        Route::get('/', 'start')->name('start');

        Route::get('{step}', 'show')
            ->whereIn('step', SurveyController::STEPS)
            ->name('show');

        Route::post('{step}', 'store')
            ->whereIn('step', SurveyController::STEPS)
            ->name('store');
    });
