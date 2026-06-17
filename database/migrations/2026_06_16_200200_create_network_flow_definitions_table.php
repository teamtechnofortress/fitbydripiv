<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_flow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('flow_key', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('steps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active', 'network_flow_definitions_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_flow_definitions');
    }
};
