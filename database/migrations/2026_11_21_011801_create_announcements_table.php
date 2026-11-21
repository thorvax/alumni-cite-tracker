<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Events and announcements posted by admins (shown on the site, optionally emailed).
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // announcement or event
            $table->string('title');
            $table->text('body'); // Plain text; line breaks are kept, HTML is never rendered.

            // Events only.
            $table->dateTime('event_starts_at')->nullable();
            $table->string('event_place')->nullable();
            $table->string('event_link')->nullable();

            // Who it is for: all, student_ids or year. The filter holds the pasted
            // IDs ({"student_ids": [...]}) or the years ({"from": 2020, "to": 2023}).
            $table->string('audience_type', 20);
            $table->json('audience_filter')->nullable();
            $table->string('visibility', 20)->default('selected'); // public or selected
            $table->boolean('send_email')->default(true);
            $table->dateTime('show_until')->nullable();

            $table->string('status', 20)->default('draft'); // draft, scheduled or sent
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
