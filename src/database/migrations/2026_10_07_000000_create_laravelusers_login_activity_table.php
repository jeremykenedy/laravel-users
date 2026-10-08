<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::connection(config('laravelusers.activity.connection'))->create('laravelusers_login_activity', function (Blueprint $table) {
            $table->string('user_key', 64)->primary();
            $table->timestamp('last_login_at');
            $table->text('ip_address')->nullable();
            $table->string('device');
            $table->string('os');
            $table->string('browser');
        });
    }

    public function down(): void
    {
        Schema::connection(config('laravelusers.activity.connection'))->dropIfExists('laravelusers_login_activity');
    }
};
