<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use App\Support\Alumni\AccountFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Activation or password reset, page 3: choose a password, then log in.
 */
class SetPasswordController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $graduate = AccountFlow::graduate($request);
        $purpose = AccountFlow::purpose($request);

        if ($graduate === null || $purpose === null) {
            return redirect('/');
        }

        if (! AccountFlow::codeVerified($request)) {
            return to_route('alumni.code.show');
        }

        return Inertia::render('alumni/set-password', [
            'purpose' => $purpose,
            'needsConsent' => $this->needsConsent($graduate, $purpose),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $graduate = AccountFlow::graduate($request);
        $purpose = AccountFlow::purpose($request);

        if ($graduate === null || $purpose === null || ! AccountFlow::codeVerified($request)) {
            return redirect('/');
        }

        $needsConsent = $this->needsConsent($graduate, $purpose);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'privacy' => $needsConsent ? ['accepted'] : [],
        ], [
            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'The two passwords do not match.',
            'privacy.accepted' => 'Please confirm that you have read and agree to the Privacy Policy.',
        ]);

        $graduate->forceFill([
            'password' => $data['password'],
            'activated_at' => $graduate->activated_at ?? now(),
            'remember_token' => Str::random(60), // logs out "remember me" on other devices
        ]);

        if ($needsConsent) {
            $graduate->privacy_consented_at = now();
        }

        $graduate->save();

        AccountFlow::forget($request);
        Auth::guard('graduate')->login($graduate);
        $request->session()->regenerate();
        $request->session()->forget('survey'); // no leftover answers from another person

        return $graduate->survey_completed_at === null
            ? to_route('survey.start')
            : to_route('alumni.my-survey');
    }

    /** Always asked when activating; on a reset only if they never agreed before. */
    private function needsConsent(Graduate $graduate, string $purpose): bool
    {
        return $purpose === GraduateLoginCode::PURPOSE_ACTIVATE || $graduate->privacy_consented_at === null;
    }
}
