<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('laravelusers_appearance_preferences', function (Blueprint $table) {
            $table->unsignedTinyInteger('gradient_strength')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laravelusers_appearance_preferences', function (Blueprint $table) {
            $table->dropColumn('gradient_strength');
        });
    }
};
