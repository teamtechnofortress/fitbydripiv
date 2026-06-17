<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('state_code', 10);
            $table->string('state_name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_code', 'state_code'], 'states_country_state_unique');
            $table->index(['country_code', 'is_active'], 'states_country_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
