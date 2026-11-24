<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Student ID Number is how a graduate is identified (and, with the
     * Last Name, how they activate their account), so every graduate must
     * have both. The unique key on student_id stays.
     */
    public function up(): void
    {
        // Never delete anything here: stop and let the admin fix the rows first.
        $missing = DB::table('graduates')
            ->where(fn ($query) => $query->whereNull('student_id')->orWhereNull('last_name'))
            ->count();

        if ($missing > 0) {
            throw new RuntimeException(
                "{$missing} graduate(s) have no Student ID Number or Last Name. "
                .'Add them in Graduates > Edit (or delete those rows), then run "php artisan migrate" again.'
            );
        }

        Schema::table('graduates', function (Blueprint $table) {
            $table->string('student_id', 20)->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->string('student_id', 20)->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }
};
