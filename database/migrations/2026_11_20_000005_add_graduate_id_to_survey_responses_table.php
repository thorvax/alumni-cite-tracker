<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            // Whose submission this is, so each graduate has an update history.
            $table->foreignId('graduate_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Link each graduate's latest submission (older ones were never linked).
        DB::table('graduates')
            ->whereNotNull('survey_response_id')
            ->orderBy('id')
            ->select(['id', 'survey_response_id'])
            ->each(function (object $graduate) {
                DB::table('survey_responses')
                    ->where('id', $graduate->survey_response_id)
                    ->update(['graduate_id' => $graduate->id]);
            });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graduate_id');
        });
    }
};
