<?php

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_faqs') && ! Schema::hasTable('faqs')) {
            Schema::rename('product_faqs', 'faqs');
        }

        if (! Schema::hasTable('faqs')) {
            return;
        }

        Schema::table('faqs', function (Blueprint $table) {
            if (! Schema::hasColumn('faqs', 'scope_type')) {
                $table->string('scope_type', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('faqs', 'scope_id')) {
                $table->uuid('scope_id')->nullable()->after('scope_type');
            }

            if (! Schema::hasColumn('faqs', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }

            if (! Schema::hasColumn('faqs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        if (Schema::hasColumn('faqs', 'product_id')) {
            DB::table('faqs')
                ->whereNull('scope_type')
                ->update([
                    'scope_type' => Faq::SCOPE_PRODUCT,
                    'scope_id' => DB::raw('product_id'),
                    'is_active' => true,
                ]);
        }

        // Depending on the database history, the renamed table may retain the
        // original FK name, or there may be no FK at all.
        try {
            DB::statement('ALTER TABLE `faqs` DROP FOREIGN KEY `product_faqs_product_id_foreign`');
        } catch (Throwable) {
        }

        try {
            DB::statement('ALTER TABLE `faqs` DROP FOREIGN KEY `faqs_product_id_foreign`');
        } catch (Throwable) {
        }

        if (Schema::hasColumn('faqs', 'product_id')) {
            try {
                DB::statement('ALTER TABLE `faqs` DROP INDEX `product_faqs_product_id_sort_order_index`');
            } catch (Throwable) {
            }

            try {
                DB::statement('ALTER TABLE `faqs` DROP INDEX `faqs_product_id_sort_order_index`');
            } catch (Throwable) {
            }

            Schema::table('faqs', function (Blueprint $table) {
                $table->dropColumn('product_id');
            });
        }

        Schema::table('faqs', function (Blueprint $table) {
            try {
                $table->index(['scope_type', 'scope_id']);
            } catch (Throwable) {
            }

            try {
                $table->index(['scope_type', 'scope_id', 'sort_order']);
            } catch (Throwable) {
            }
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->after('id');
        });

        DB::table('faqs')
            ->where('scope_type', Faq::SCOPE_PRODUCT)
            ->update(['product_id' => DB::raw('scope_id')]);

        Schema::table('faqs', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index(['product_id', 'sort_order']);
            $table->dropIndex(['scope_type', 'scope_id']);
            $table->dropIndex(['scope_type', 'scope_id', 'sort_order']);
            $table->dropColumn(['scope_type', 'scope_id', 'is_active', 'updated_at']);
        });

        Schema::rename('faqs', 'product_faqs');
    }
};
