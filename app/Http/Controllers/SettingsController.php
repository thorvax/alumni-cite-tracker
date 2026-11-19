<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const FLAGS = ['allow_registration', 'email_notifications', 'activity_logs'];

    public function show(Request $request): Response
    {
        $user = $request->user('web');

        return Inertia::render('admin/settings', [
            'account' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'position' => $user?->getAttribute('position'),
            ],
            'system' => collect(self::FLAGS)
                ->mapWithKeys(fn (string $flag) => [$flag => AppSetting::flag($flag)])
                ->all(),
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        abort_if($user === null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        $user->forceFill($data)->save();

        return back();
    }

    public function updateSystem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'allow_registration' => ['required', 'boolean'],
            'email_notifications' => ['required', 'boolean'],
            'activity_logs' => ['required', 'boolean'],
        ]);

        foreach (self::FLAGS as $flag) {
            AppSetting::put($flag, (bool) $data[$flag]);
        }

        return back();
    }
}
