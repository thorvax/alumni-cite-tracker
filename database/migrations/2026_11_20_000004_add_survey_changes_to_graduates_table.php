<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            // Survey parts that changed in the last update, e.g. ["Employment Data"].
            // [] = updated with no changes; null = never updated (first answer only).
            $table->json('survey_changes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn('survey_changes');
        });
    }
};
