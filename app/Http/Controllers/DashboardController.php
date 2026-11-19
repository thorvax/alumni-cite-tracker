<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $total = Graduate::count();
        $employed = Graduate::whereIn('employment_status', Graduate::EMPLOYED_STATUSES)->count();
        $unemployed = Graduate::where('employment_status', 'Unemployed')->count();
        $further = Graduate::where('employment_status', 'Further Studies')->count();

        $percent = fn (int $count): int => $total > 0 ? (int) round($count / $total * 100) : 0;

        return Inertia::render('admin/dashboard', [
            'today' => now()->format('F j, Y'),
            'stats' => [
                'total' => $total,
                'employed' => ['count' => $employed, 'percent' => $percent($employed)],
                'unemployed' => ['count' => $unemployed, 'percent' => $percent($unemployed)],
                'further' => ['count' => $further, 'percent' => $percent($further)],
            ],
            'recent' => Graduate::query()
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Graduate $graduate) => $graduate->toRow())
                ->values(),
        ]);
    }
}
