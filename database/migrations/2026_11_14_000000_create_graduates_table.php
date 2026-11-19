<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('current_address')->nullable();
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('course')->nullable();
            $table->unsignedSmallInteger('year_graduated')->nullable()->index();
            $table->string('employment_status')->nullable()->index();
            // Every survey answer, keyed by step slug. Empty for graduates added by hand.
            $table->json('survey')->nullable();
            $table->foreignId('survey_response_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduates');
    }
};
