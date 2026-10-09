<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    private function connection(): ?string
    {
        $model = config('laravelusers.defaultUserModel');

        return (new $model())->getConnectionName();
    }

    public function up(): void
    {
        Schema::connection($this->connection())->create('laravelusers_avatar_preferences', function (Blueprint $table) {
            $table->string('user_key', 64)->primary();
            $table->string('source', 16);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists('laravelusers_avatar_preferences');
    }
};
