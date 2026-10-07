<?php

use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::get('survey/general-information', [SurveyController::class, 'generalInformation'])
    ->name('survey.general');

Route::post('survey/general-information', [SurveyController::class, 'storeGeneralInformation'])
    ->name('survey.general.store');
