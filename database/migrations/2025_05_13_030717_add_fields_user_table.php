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
            $table->date("birthday")->default(null)->nullable();
            $table->string("ssn")->nullable();
            $table->string("address")->nullable();
            $table->string("city")->nullable();
            $table->string("state")->nullable();
            $table->string("zip")->nullable();
            $table->string("phone")->nullable();
            $table->string("emergency")->nullable();
            $table->string("contact")->nullable();
            $table->string("gender")->nullable();
            $table->double("hourly_rate")->nullable();
            $table->date("hiring_date")->nullable();
            $table->string("title")->nullable();
            $table->string("payment_method")->nullable();
            $table->string("bank")->nullable();
            $table->string("routing")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("birthday");
            $table->dropColumn("ssn");
            $table->dropColumn("address");
            $table->dropColumn("city");
            $table->dropColumn("state");
            $table->dropColumn("zip");
            $table->dropColumn("phone");
            $table->dropColumn("emergency");
            $table->dropColumn("contact");
            $table->dropColumn("gender");
            $table->dropColumn("hourly_rate");
            $table->dropColumn("hiring_date");
            $table->dropColumn("title");
            $table->dropColumn("payment_method");
            $table->dropColumn("back");
            $table->dropColumn("routing");
        });
    }
};
