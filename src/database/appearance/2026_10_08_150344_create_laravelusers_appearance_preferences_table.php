<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('laravelusers_appearance_preferences', function (Blueprint $table) {
            $table->string('user_key', 64)->primary();
            $table->string('color', 7)->nullable();
            $table->boolean('gradient')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laravelusers_appearance_preferences');
    }
};
