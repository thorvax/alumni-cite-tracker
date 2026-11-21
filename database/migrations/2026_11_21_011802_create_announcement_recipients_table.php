<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per graduate an announcement was sent to (email status + read time).
        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('graduate_id')->constrained()->cascadeOnDelete();
            $table->string('email_status', 20)->default('pending'); // pending, queued, sent, failed, skipped
            $table->string('email_error')->nullable();
            $table->dateTime('emailed_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'graduate_id']);
            $table->index(['graduate_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_recipients');
    }
};
