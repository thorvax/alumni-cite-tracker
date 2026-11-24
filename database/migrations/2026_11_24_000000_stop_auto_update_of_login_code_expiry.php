<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * On MySQL/MariaDB (like XAMPP), a required "timestamp" column can get
     * "ON UPDATE CURRENT_TIMESTAMP", so every wrong code typed (the tries
     * counter going up) moved the expiry time. A "datetime" column never
     * changes by itself.
     */
    public function up(): void
    {
        Schema::table('graduate_login_codes', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
        });
    }

    public function down(): void
    {
        Schema::table('graduate_login_codes', function (Blueprint $table) {
            $table->timestamp('expires_at')->change();
        });
    }
};
