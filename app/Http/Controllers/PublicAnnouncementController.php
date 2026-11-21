<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * /announcements: public announcements anyone can read without logging in.
 * Announcements for selected alumni are never shown here.
 */
class PublicAnnouncementController extends Controller
{
    public function index(): Response
    {
        $page = Announcement::query()
            ->publiclyVisible()
            ->latest('published_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('announcements/index', [
            'announcements' => $page->through(fn (Announcement $a) => $a->toReaderArray()),
        ]);
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        abort_unless(Announcement::query()->publiclyVisible()->whereKey($announcement->id)->exists(), 404);

        // Opened from the email by a graduate who is logged in: count it as read.
        $graduate = $request->user('graduate');
        if ($graduate instanceof Graduate) {
            $announcement->markReadBy($graduate);
        }

        return Inertia::render('announcements/show', [
            'announcement' => $announcement->toReaderArray(),
        ]);
    }
}
