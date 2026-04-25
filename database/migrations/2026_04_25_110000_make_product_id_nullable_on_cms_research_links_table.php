<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_research_links', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('cms_research_links', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->change();
        });

        Schema::table('cms_research_links', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('cms_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cms_research_links', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('cms_research_links', function (Blueprint $table) {
            $table->uuid('product_id')->nullable(false)->change();
        });

        Schema::table('cms_research_links', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('cms_products')
                ->onDelete('cascade');
        });
    }
};
