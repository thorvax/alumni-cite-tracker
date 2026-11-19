<?php

namespace Database\Seeders;

use App\Models\Graduate;
use Illuminate\Database\Seeder;

class DemoGraduatesSeeder extends Seeder
{
    /**
     * Fills the app with sample graduates so the dashboard and reports have data.
     * Run with: php artisan db:seed --class=DemoGraduatesSeeder
     */
    public function run(): void
    {
        Graduate::factory()->count(250)->create();
    }
}
