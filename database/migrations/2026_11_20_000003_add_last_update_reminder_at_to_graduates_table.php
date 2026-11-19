<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            // Last "please update your answers" email (every 6 or 12 months).
            $table->timestamp('last_update_reminder_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn('last_update_reminder_at');
        });
    }
};
