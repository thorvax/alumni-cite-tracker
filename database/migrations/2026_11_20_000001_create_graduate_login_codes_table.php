<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 6-digit codes emailed to graduates to activate their account or reset their password.
        Schema::create('graduate_login_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduate_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash'); // Never the plain code.
            $table->string('purpose', 20); // activate or reset
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['graduate_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduate_login_codes');
    }
};
