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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean("status")->nullable();   
            $table->boolean("password_reset")->nullable();         
            $table->boolean("require_signature")->nullable();         
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("status");
            $table->dropColumn("password_reset");
            $table->dropColumn("require_signature");
        });
    }
};
