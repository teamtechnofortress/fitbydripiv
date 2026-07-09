<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_config_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->morphs('auditable');
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->foreignId('actor_id')->constrained('users');
            $table->string('actor_role')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_config_audit_logs');
    }
};
