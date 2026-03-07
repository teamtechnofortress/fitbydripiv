<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_hours', function (Blueprint $table) {
            $table->string("twillio_sid")->nullable();            
            $table->string("twillio_auth_token")->nullable();            
            $table->string("twillio_phone_number")->nullable();            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_hours', function (Blueprint $table) {
            $table->dropColumn("twillio_sid");
            $table->dropColumn("twillio_auth_token");
            $table->dropColumn("twillio_phone_number");
        });
    }
};
