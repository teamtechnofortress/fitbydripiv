<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('orders', 'orders_product_id_foreign');
        $this->dropForeignIfExists('subscriptions', 'subscriptions_product_id_foreign');

        if (! Schema::hasColumn('orders', 'pricing_option_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->uuid('pricing_option_id')->nullable()->after('pricing_type');
                $table->foreign('pricing_option_id')
                    ->references('id')
                    ->on('pricing_options')
                    ->nullOnDelete();
            });
        }

        DB::statement("
            UPDATE `orders`
            SET `pricing_type` = `purchase_type`
            WHERE `pricing_type` IN ('base', 'micro', 'micro_dose', 'sample')
        ");

        DB::statement("
            ALTER TABLE `orders`
            MODIFY `pricing_type` ENUM('one_time', 'subscription') NOT NULL
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'pricing_option_id')) {
            $this->dropForeignIfExists('orders', 'orders_pricing_option_id_foreign');

            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('pricing_option_id');
            });
        }

        DB::statement("
            UPDATE `orders`
            SET `pricing_type` = 'base'
            WHERE `pricing_type` IN ('one_time', 'subscription')
        ");

        DB::statement("
            ALTER TABLE `orders`
            MODIFY `pricing_type` ENUM('base', 'micro', 'micro_dose', 'sample') NOT NULL
        ");
    }

    protected function dropForeignIfExists(string $table, string $foreignKey): void
    {
        try {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $foreignKey));
        } catch (\Throwable) {
        }
    }
};
