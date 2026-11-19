<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            // Sample rows from DemoGraduatesSeeder; removed with `php artisan cite:delete-demo`.
            $table->boolean('is_demo')->default(false)->index();
        });

        // Mark demo rows seeded before this column existed. They have no Student ID,
        // no invite link, no survey answers, and a fake email on a reserved
        // domain (example.com / .org / .net) that no real person can own.
        DB::table('graduates')
            ->whereNull('student_id')
            ->whereNull('invite_token')
            ->whereNull('survey')
            ->whereNull('survey_response_id')
            ->where(fn ($query) => $query
                ->where('email', 'like', '%@example.com')
                ->orWhere('email', 'like', '%@example.org')
                ->orWhere('email', 'like', '%@example.net'))
            ->update(['is_demo' => true]);
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
