<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_research_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('title');
            $table->string('authors')->nullable();
            $table->string('journal')->nullable();
            $table->integer('publication_year')->nullable();
            $table->string('pubmed_id')->nullable();
            $table->string('doi')->nullable();
            $table->string('article_url')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('cms_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_research_links');
    }
};
