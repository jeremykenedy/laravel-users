<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('laravelusers_email_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_key', 64)->unique();
            $table->text('old_email');
            $table->text('new_email');
            $table->string('old_token_hash', 64);
            $table->string('new_token_hash', 64);
            $table->string('fingerprint', 64);
            $table->unsignedBigInteger('old_confirmed_at')->nullable();
            $table->unsignedBigInteger('new_confirmed_at')->nullable();
            $table->unsignedBigInteger('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laravelusers_email_changes');
    }
};
