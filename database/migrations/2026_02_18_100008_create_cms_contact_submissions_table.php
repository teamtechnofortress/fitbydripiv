<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_contact_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->string('submission_type')->default('general');
            $table->uuid('product_id')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_contact_submissions');
    }
};
