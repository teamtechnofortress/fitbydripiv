<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('patient_id')
                ->references('id')
                ->on('patient')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('patient_id')
                ->references('id')
                ->on('patient')
                ->cascadeOnDelete();
        });
    }
};
