<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dr_network_config_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dr_network_id')->constrained('dr_networks')->cascadeOnDelete();
            $table->string('key', 100);
            $table->longText('value')->nullable();
            $table->enum('value_type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->boolean('is_secret')->default(true);
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['dr_network_id', 'key'], 'dr_network_config_values_network_key_unique');
            $table->index(['dr_network_id', 'is_secret'], 'dr_network_config_values_network_secret_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dr_network_config_values');
    }
};
