<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\GraduateLoginCode;
use App\Services\LoginCodeService;
use App\Support\Alumni\AccountFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Activation or password reset, page 2: the 6-digit code from the email.
 * Also serves the decoy flow (see AccountFlow), where every code is wrong.
 */
class VerifyCodeController extends Controller
{
    public function __construct(private LoginCodeService $codes) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $graduate = AccountFlow::graduate($request);
        $purpose = AccountFlow::purpose($request);
        $decoy = AccountFlow::isDecoy($request);

        if ($purpose === null || ($graduate === null && ! $decoy)) {
            return redirect('/');
        }

        if (AccountFlow::codeVerified($request)) {
            return to_route('alumni.password.show');
        }

        return Inertia::render('alumni/verify-code', [
            'purpose' => $purpose,
            // Only shown when activating: a reset must not reveal whether the details matched.
            'email' => $purpose === GraduateLoginCode::PURPOSE_ACTIVATE && $graduate !== null
                ? $this->mask((string) $graduate->email)
                : null,
            'resendIn' => $graduate !== null
                ? $this->codes->secondsUntilResend($graduate, $purpose)
                : AccountFlow::decoySecondsUntilResend($request),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $graduate = AccountFlow::graduate($request);
        $purpose = AccountFlow::purpose($request);
        $decoy = AccountFlow::isDecoy($request);

        if ($purpose === null || ($graduate === null && ! $decoy)) {
            return redirect('/');
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Please enter the 6-digit code from your email.',
            'code.digits' => 'The code has 6 digits.',
        ]);

        $error = $graduate !== null
            ? $this->codes->verify($graduate, $purpose, $data['code'])
            : AccountFlow::decoyFailedAttempt($request);

        if ($error !== null) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        AccountFlow::markCodeVerified($request);

        return to_route('alumni.password.show');
    }

    public function resend(Request $request): RedirectResponse
    {
        $graduate = AccountFlow::graduate($request);
        $purpose = AccountFlow::purpose($request);
        $decoy = AccountFlow::isDecoy($request);

        if ($purpose === null || ($graduate === null && ! $decoy)) {
            return redirect('/');
        }

        $wait = $graduate !== null
            ? $this->codes->secondsUntilResend($graduate, $purpose)
            : AccountFlow::decoySecondsUntilResend($request);

        if ($wait > 0) {
            throw ValidationException::withMessages([
                'code' => "Please wait {$wait} seconds before asking for a new code.",
            ]);
        }

        if ($graduate !== null) {
            $this->codes->send($graduate, $purpose);
        } else {
            AccountFlow::decoyResent($request);
        }

        return back()->with('status', 'We sent you a new code.');
    }

    /** juan.cruz@gmail.com -> ju********@gmail.com, so people know which inbox to check. */
    private function mask(string $email): string
    {
        $name = Str::before($email, '@');
        $domain = Str::after($email, '@');

        return Str::mask($name, '*', 2).'@'.$domain;
    }
}
