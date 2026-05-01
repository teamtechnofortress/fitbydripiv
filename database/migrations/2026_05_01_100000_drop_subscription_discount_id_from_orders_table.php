<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['subscription_discount_id']);
            $table->dropColumn('subscription_discount_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('subscription_discount_id')->nullable()->after('pricing_option_id');

            $table->foreign('subscription_discount_id')
                ->references('id')
                ->on('cms_subscription_discounts')
                ->nullOnDelete();
        });
    }
};
