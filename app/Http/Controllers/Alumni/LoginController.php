<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Graduate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alumni log in with their Student ID Number and password (the "graduate"
 * guard). Admins keep using /login.
 */
class LoginController extends Controller
{
    /** Failed tries allowed per Student ID Number + IP address, per minute. */
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request): Response|RedirectResponse
    {
        if (Auth::guard('graduate')->check()) {
            return to_route('alumni.my-survey');
        }

        return Inertia::render('alumni/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ], [
            'student_id.required' => 'Please enter your Student ID Number.',
            'password.required' => 'Please enter your password.',
        ]);

        $key = 'alumni-login|'.trim($data['student_id']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Only activated accounts can log in.
        $credentials = [
            'student_id' => trim($data['student_id']),
            'password' => $data['password'],
            fn ($query) => $query->whereNotNull('activated_at'),
        ];

        if (! Auth::guard('graduate')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            // Same message whatever was wrong, so it does not reveal which Student IDs exist.
            throw ValidationException::withMessages([
                'login' => 'The Student ID Number or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Never show someone else's unfinished answers from this browser.
        $request->session()->forget('survey');

        /** @var Graduate $graduate */
        $graduate = Auth::guard('graduate')->user();

        return redirect()->intended(
            $graduate->survey_completed_at === null ? route('survey.start') : route('alumni.my-survey'),
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('graduate')->logout();

        // Only the alumni login is removed; an admin logged in on the same
        // browser stays logged in. A new session ID and CSRF token are still
        // issued, and their unfinished survey answers are removed.
        $request->session()->forget('survey');
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return to_route('alumni.login');
    }
}
