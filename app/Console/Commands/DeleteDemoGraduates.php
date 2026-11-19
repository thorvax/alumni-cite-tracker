<?php

namespace App\Console\Commands;

use App\Models\Graduate;
use Illuminate\Console\Command;

/**
 * Deletes the sample graduates made by DemoGraduatesSeeder (is_demo = true).
 * Real graduates (uploaded, added by hand, or from the survey) are never touched.
 */
class DeleteDemoGraduates extends Command
{
    protected $signature = 'cite:delete-demo {--force : Delete without asking}';

    protected $description = 'Delete the demo graduates created by DemoGraduatesSeeder';

    public function handle(): int
    {
        $demo = Graduate::query()->where('is_demo', true);
        $count = $demo->count();

        if ($count === 0) {
            $this->info('There are no demo graduates to delete.');

            return self::SUCCESS;
        }

        $real = Graduate::query()->where('is_demo', false)->count();
        $this->line("Found {$count} demo graduate(s). {$real} real graduate(s) will be kept.");

        if (! $this->option('force') && ! $this->confirm('Delete the demo graduates?')) {
            $this->line('Nothing was deleted.');

            return self::SUCCESS;
        }

        $demo->delete();

        $this->info("Deleted {$count} demo graduate(s).");

        return self::SUCCESS;
    }
}
