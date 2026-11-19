<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            // Filled by the bulk upload. Graduates added by hand or through the open survey leave these empty.
            $table->string('student_id', 20)->nullable()->unique()->after('id');
            $table->string('last_name')->nullable()->after('full_name');

            // The personal survey link we email, and what happened to the email.
            $table->string('invite_token', 64)->nullable()->unique();
            $table->string('invite_status')->nullable()->index();
            $table->timestamp('invite_sent_at')->nullable();
            $table->text('invite_error')->nullable();
            $table->unsignedSmallInteger('reminder_count')->default(0);
            $table->timestamp('last_reminded_at')->nullable();

            $table->timestamp('survey_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
            $table->dropUnique(['invite_token']);
            $table->dropIndex(['invite_status']);

            $table->dropColumn([
                'student_id',
                'last_name',
                'invite_token',
                'invite_status',
                'invite_sent_at',
                'invite_error',
                'reminder_count',
                'last_reminded_at',
                'survey_completed_at',
            ]);
        });
    }
};
