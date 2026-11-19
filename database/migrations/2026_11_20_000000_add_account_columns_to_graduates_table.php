<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            // Alumni login. Empty until the graduate activates their account from the email link.
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('activated_at')->nullable();

            // When they ticked "I have read and agree to the Privacy Policy".
            $table->timestamp('privacy_consented_at')->nullable();

            // Last time they updated their answers (survey_completed_at keeps the first submit).
            $table->timestamp('survey_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('graduates', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'remember_token',
                'activated_at',
                'privacy_consented_at',
                'survey_updated_at',
            ]);
        });
    }
};
