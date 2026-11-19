<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use App\Services\LoginCodeService;
use App\Support\Alumni\AccountFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * First-time activation, page 1: the graduate opened the link from their
 * email and proves who they are with their Student ID Number and Last Name.
 */
class ActivationController extends Controller
{
    /** Session key: the graduate whose email link was opened. */
    private const LINK_KEY = 'alumni_activation_link';

    public function __construct(private LoginCodeService $codes) {}

    /**
     * GET /survey/start/{token}: the personal link from the invitation email.
     */
    public function link(Request $request, string $token): RedirectResponse
    {
        $graduate = Graduate::query()->where('invite_token', $token)->first();

        if ($graduate === null) {
            return redirect('/');
        }

        // Once the account is active the link only opens the login page.
        if ($graduate->isActivated()) {
            return to_route('alumni.login');
        }

        AccountFlow::forget($request);
        $request->session()->put(self::LINK_KEY, $graduate->id);

        return to_route('alumni.activate.show');
    }

    public function show(Request $request): Response|RedirectResponse
    {
        if ($this->linkedGraduate($request) === null) {
            return redirect('/');
        }

        return Inertia::render('alumni/activate');
    }

    public function store(Request $request): RedirectResponse
    {
        $graduate = $this->linkedGraduate($request);

        if ($graduate === null) {
            return redirect('/');
        }

        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:20'],
            'last_name' => ['required', 'string', 'max:255'],
        ], [
            'student_id.required' => 'Please enter your Student ID Number.',
            'last_name.required' => 'Please enter your Last Name.',
        ]);

        $matches = trim($data['student_id']) === $graduate->student_id
            && $graduate->hasLastName($data['last_name']);

        // One message for both fields, so nobody can tell which one was wrong.
        if (! $matches) {
            throw ValidationException::withMessages([
                'identity' => 'The details you entered do not match our records.',
            ]);
        }

        if (blank($graduate->email)) {
            throw ValidationException::withMessages([
                'identity' => 'We do not have an email address on file for you. Please contact the CITE office.',
            ]);
        }

        AccountFlow::start($request, $graduate, GraduateLoginCode::PURPOSE_ACTIVATE);

        // Going back and submitting again should not send a second code within a minute.
        if ($this->codes->secondsUntilResend($graduate, GraduateLoginCode::PURPOSE_ACTIVATE) === 0) {
            $this->codes->send($graduate, GraduateLoginCode::PURPOSE_ACTIVATE);
        }

        return to_route('alumni.code.show');
    }

    private function linkedGraduate(Request $request): ?Graduate
    {
        $id = $request->session()->get(self::LINK_KEY);
        $graduate = $id === null ? null : Graduate::query()->whereKey($id)->first();

        return $graduate !== null && ! $graduate->isActivated() ? $graduate : null;
    }
}
