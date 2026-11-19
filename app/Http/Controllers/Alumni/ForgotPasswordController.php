<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use App\Services\LoginCodeService;
use App\Support\Alumni\AccountFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Forgot password, page 1: Student ID Number + Last Name. Then the same code
 * and password pages as activation (VerifyCodeController, SetPasswordController).
 */
class ForgotPasswordController extends Controller
{
    public function __construct(private LoginCodeService $codes) {}

    public function show(): Response|RedirectResponse
    {
        if (Auth::guard('graduate')->check()) {
            return to_route('alumni.my-survey');
        }

        return Inertia::render('alumni/forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:20'],
            'last_name' => ['required', 'string', 'max:255'],
        ], [
            'student_id.required' => 'Please enter your Student ID Number.',
            'last_name.required' => 'Please enter your Last Name.',
        ]);

        // Only activated accounts can reset. Not activated yet = use the email link.
        $graduate = Graduate::query()
            ->where('student_id', trim($data['student_id']))
            ->whereNotNull('activated_at')
            ->first();

        $matches = $graduate !== null
            && $graduate->hasLastName($data['last_name'])
            && filled($graduate->email);

        // Matching or not, the next page looks the same (see AccountFlow).
        AccountFlow::start($request, $matches ? $graduate : null, GraduateLoginCode::PURPOSE_RESET);

        if ($matches && $this->codes->secondsUntilResend($graduate, GraduateLoginCode::PURPOSE_RESET) === 0) {
            $this->codes->send($graduate, GraduateLoginCode::PURPOSE_RESET);
        }

        return to_route('alumni.code.show');
    }
}
