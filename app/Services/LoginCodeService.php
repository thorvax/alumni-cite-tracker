<?php

namespace App\Services;

use App\Mail\LoginCodeMail;
use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Creates, emails and checks the 6-digit codes used to activate an account
 * or reset a password. Only a hash of each code is saved.
 */
class LoginCodeService
{
    public const EXPIRED = 'This code has expired or was already used. Please request a new code.';

    public const LOCKED = 'Too many wrong attempts. Please request a new code.';

    public const WRONG = 'The code is incorrect. Please check your email and try again.';

    /**
     * Emails a new code. Any older unused code for the same purpose stops working.
     */
    public function send(Graduate $graduate, string $purpose): void
    {
        $graduate->loginCodes()->where('purpose', $purpose)->whereNull('used_at')->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $graduate->loginCodes()->create([
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(GraduateLoginCode::EXPIRES_MINUTES),
        ]);

        Mail::to((string) $graduate->email)->queue(new LoginCodeMail($graduate, $code, $purpose));
    }

    /**
     * Seconds left before another code may be sent (0 = can send now).
     */
    public function secondsUntilResend(Graduate $graduate, string $purpose): int
    {
        $latest = $graduate->loginCodes()->where('purpose', $purpose)->latest('id')->first();

        if ($latest?->created_at === null) {
            return 0;
        }

        $waited = (int) $latest->created_at->diffInSeconds(now());

        return max(0, GraduateLoginCode::RESEND_SECONDS - $waited);
    }

    /**
     * Checks the code. Returns null when it is correct (and marks it used),
     * otherwise the message to show.
     */
    public function verify(Graduate $graduate, string $purpose, string $code): ?string
    {
        $record = $graduate->loginCodes()
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if ($record === null || $record->expires_at->isPast()) {
            return self::EXPIRED;
        }

        if ($record->attempts >= GraduateLoginCode::MAX_ATTEMPTS) {
            return self::LOCKED;
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            return $record->attempts >= GraduateLoginCode::MAX_ATTEMPTS ? self::LOCKED : self::WRONG;
        }

        $record->update(['used_at' => now()]);

        return null;
    }
}
