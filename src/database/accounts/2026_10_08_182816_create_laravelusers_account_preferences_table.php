<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('laravelusers_account_preferences', function (Blueprint $table) {
            $table->string('user_key', 64)->primary();
            $table->string('user_type');
            $table->boolean('enabled')->nullable();
            $table->boolean('settings_enabled')->nullable();
            $table->string('full_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laravelusers_account_preferences');
    }
};
