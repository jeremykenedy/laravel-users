<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        $schema = Schema::connection(config('laravelusers-packages.database'));
        if ($schema->hasTable('laravelusers_package_jobs')) {
            return;
        }
        $schema->create('laravelusers_package_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection(config('laravelusers-packages.database'))->dropIfExists('laravelusers_package_jobs');
    }
};
