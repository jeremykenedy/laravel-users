<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('laravelusers_settings', function (Blueprint $table) {
            $table->string('key', 32)->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laravelusers_settings');
    }
};
