<?php

namespace App\Support\Alumni;

use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use App\Services\LoginCodeService;
use Illuminate\Http\Request;

/**
 * Remembers, in the session, which graduate is activating their account or
 * resetting their password, and how far they got:
 * identity checked -> code verified -> password set.
 *
 * Forgot password with details that do not match starts a "decoy" flow: no
 * graduate and no email, but the code page looks and behaves the same, so
 * nobody can use the form to find out which Student ID Numbers exist.
 */
class AccountFlow
{
    private const KEY = 'alumni_flow';

    /** Called after page 1. $graduate is null for a decoy password reset. */
    public static function start(Request $request, ?Graduate $graduate, string $purpose): void
    {
        $request->session()->put(self::KEY, [
            'graduate_id' => $graduate?->id,
            'purpose' => $purpose,
            'code_verified' => false,
            'sent_at' => now()->getTimestamp(), // decoy only
            'attempts' => 0, // decoy only
        ]);
    }

    public static function graduate(Request $request): ?Graduate
    {
        $id = $request->session()->get(self::KEY.'.graduate_id');

        return $id === null ? null : Graduate::query()->whereKey($id)->first();
    }

    /** activate or reset (see GraduateLoginCode::PURPOSE_*). */
    public static function purpose(Request $request): ?string
    {
        $purpose = $request->session()->get(self::KEY.'.purpose');

        return is_string($purpose) ? $purpose : null;
    }

    public static function isDecoy(Request $request): bool
    {
        return self::purpose($request) === GraduateLoginCode::PURPOSE_RESET
            && $request->session()->get(self::KEY.'.graduate_id') === null;
    }

    /** Same 60-second wait as a real code. */
    public static function decoySecondsUntilResend(Request $request): int
    {
        $sentAt = (int) $request->session()->get(self::KEY.'.sent_at', 0);

        return max(0, GraduateLoginCode::RESEND_SECONDS - (now()->getTimestamp() - $sentAt));
    }

    public static function decoyResent(Request $request): void
    {
        $request->session()->put(self::KEY.'.sent_at', now()->getTimestamp());
        $request->session()->put(self::KEY.'.attempts', 0);
    }

    /** A decoy code is always wrong, with the same messages as a real one. */
    public static function decoyFailedAttempt(Request $request): string
    {
        $attempts = (int) $request->session()->get(self::KEY.'.attempts', 0) + 1;
        $request->session()->put(self::KEY.'.attempts', $attempts);

        return $attempts >= GraduateLoginCode::MAX_ATTEMPTS ? LoginCodeService::LOCKED : LoginCodeService::WRONG;
    }

    public static function markCodeVerified(Request $request): void
    {
        $request->session()->put(self::KEY.'.code_verified', true);
    }

    public static function codeVerified(Request $request): bool
    {
        return $request->session()->get(self::KEY.'.code_verified') === true;
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}
