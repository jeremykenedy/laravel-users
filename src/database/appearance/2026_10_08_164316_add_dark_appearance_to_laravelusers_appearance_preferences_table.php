<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('laravelusers_appearance_preferences', function (Blueprint $table) {
            $table->string('dark_color', 7)->nullable();
            $table->boolean('dark_gradient')->nullable();
            $table->unsignedTinyInteger('dark_gradient_strength')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laravelusers_appearance_preferences', function (Blueprint $table) {
            $table->dropColumn(['dark_color', 'dark_gradient', 'dark_gradient_strength']);
        });
    }
};
