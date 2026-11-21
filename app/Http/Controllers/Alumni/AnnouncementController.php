<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Announcements for the logged-in graduate: public ones plus the ones sent
 * to them, newest first. Opening one marks it read.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $graduate = $this->graduate($request);

        $page = Announcement::query()
            ->visibleTo($graduate)
            // Only this graduate's own row (for the unread dot).
            ->with(['recipients' => fn ($q) => $q->where('graduate_id', $graduate->id)])
            ->latest('published_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('alumni/announcements/index', [
            'announcements' => $page->through(fn (Announcement $a) => [
                ...$a->toReaderArray(),
                'unread' => $a->recipients->contains(fn (AnnouncementRecipient $r) => $r->read_at === null),
            ]),
        ]);
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        $graduate = $this->graduate($request);

        // Not sent to them, not public, or no longer showing: same as not existing.
        abort_unless(Announcement::query()->visibleTo($graduate)->whereKey($announcement->id)->exists(), 404);

        $announcement->markReadBy($graduate);

        return Inertia::render('alumni/announcements/show', [
            'announcement' => $announcement->toReaderArray(),
        ]);
    }

    private function graduate(Request $request): Graduate
    {
        /** @var Graduate $graduate */
        $graduate = $request->user('graduate');

        return $graduate;
    }
}
