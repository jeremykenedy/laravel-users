<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        $model = config('laravelusers.defaultUserModel');
        Schema::connection((new $model())->getConnectionName())->create('laravelusers_account_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_type');
            $table->string('user_id');
            $table->string('action', 20);
            $table->string('token_hash', 64);
            $table->string('deleted_fingerprint', 64);
            $table->unsignedBigInteger('expires_at')->nullable()->index();
            $table->unsignedBigInteger('consumed_at')->nullable();
            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        $model = config('laravelusers.defaultUserModel');
        Schema::connection((new $model())->getConnectionName())->dropIfExists('laravelusers_account_links');
    }
};
