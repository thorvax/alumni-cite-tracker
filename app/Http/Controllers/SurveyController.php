<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SurveyController extends Controller
{
    private const GENERAL_KEY = 'survey.general_information';

    public function generalInformation(Request $request): Response
    {
        return Inertia::render('survey/general-information', [
            // Restores what the person already typed when they come back to this step.
            'draft' => $request->session()->get(self::GENERAL_KEY, []),
        ]);
    }

    public function storeGeneralInformation(Request $request): RedirectResponse
    {
        $mobile = ['regex:/^(09|\+639)[\d\s-]{9,12}$/'];

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'civil_status' => ['required', 'in:Single,Married,Widowed,Separated,Divorced'],
            'sex' => ['required', 'in:male,female'],
            'birth_date' => ['required', 'date', 'before:today'],
            'region' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', 'max:100'],
            'region_code' => ['required', 'string', 'max:20'],
            'province_code' => ['nullable', 'string', 'max:20'],
            'city_code' => ['required', 'string', 'max:20'],
            'barangay_code' => ['required', 'string', 'max:20'],
            'house_no' => ['nullable', 'string', 'max:150'],
            'mobile_primary' => ['required', ...$mobile],
            'mobile_secondary' => ['nullable', ...$mobile],
        ]);

        $request->session()->put(self::GENERAL_KEY, $data);

        // TODO: redirect to step B (Educational Background) once that page exists.
        return back();
    }
}
