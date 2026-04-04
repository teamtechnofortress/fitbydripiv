<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `orders` MODIFY `pricing_type` ENUM('base', 'micro', 'micro_dose', 'sample') NOT NULL"
        );

        DB::table('orders')
            ->where('pricing_type', 'micro')
            ->update(['pricing_type' => 'micro_dose']);

        DB::statement(
            "ALTER TABLE `orders` MODIFY `pricing_type` ENUM('base', 'micro_dose', 'sample') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE `orders` MODIFY `pricing_type` ENUM('base', 'micro', 'micro_dose', 'sample') NOT NULL"
        );

        DB::table('orders')
            ->where('pricing_type', 'micro_dose')
            ->update(['pricing_type' => 'micro']);

        DB::statement(
            "ALTER TABLE `orders` MODIFY `pricing_type` ENUM('base', 'micro', 'sample') NOT NULL"
        );
    }
};
