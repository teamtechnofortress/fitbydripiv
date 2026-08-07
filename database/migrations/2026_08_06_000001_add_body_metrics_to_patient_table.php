<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient', function (Blueprint $table): void {
            $table->decimal('height', 6, 2)->nullable()->after('zip');
            $table->decimal('weight', 6, 2)->nullable()->after('height');
            $table->decimal('bmi', 5, 2)->nullable()->after('weight');
        });
    }

    public function down(): void
    {
        Schema::table('patient', function (Blueprint $table): void {
            $table->dropColumn([
                'height',
                'weight',
                'bmi',
            ]);
        });
    }
};
