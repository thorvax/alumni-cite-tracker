<?php

namespace App\Http\Middleware;

use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                // Always the admin login. Named on purpose: on alumni pages the
                // "auth:graduate" middleware makes "graduate" the default guard,
                // and user() would then return the graduate here.
                'user' => $request->user('web'),
            ],
            // The logged-in graduate (alumni login): the Log out button and the
            // landing page button ("Start the survey" or "Go to My Survey").
            // A closure, so it is worked out after the page ran (opening an
            // announcement first marks it read, then the unread count is taken).
            'alumni' => fn () => $this->alumni($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Only what the pages need about the logged-in graduate (no other personal data).
     *
     * @return array{last_name: string|null, answered: bool, unread_announcements: int}|null
     */
    private function alumni(Request $request): ?array
    {
        $graduate = $request->user('graduate');

        if (! $graduate instanceof Graduate) {
            return null;
        }

        return [
            'last_name' => $graduate->last_name,
            'answered' => $graduate->survey_completed_at !== null,
            'unread_announcements' => $graduate->unreadAnnouncementCount(),
        ];
    }
}
