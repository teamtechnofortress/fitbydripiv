<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('adapter_key', 100)->unique();
            $table->enum('integration_mode', ['api', 'manual'])->default('api');
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('config_version')->default(1);
            $table->timestamps();

            $table->index(['status', 'is_default'], 'dr_networks_status_default_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_networks');
    }
};
