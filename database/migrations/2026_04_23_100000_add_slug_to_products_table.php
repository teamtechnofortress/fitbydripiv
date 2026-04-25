<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        DB::table('products')
            ->select(['id', 'name'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->each(function ($product): void {
                $slug = $this->generateUniqueSlug($product->name, $product->id);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    protected function generateUniqueSlug(?string $name, string $productId): string
    {
        $baseSlug = Str::slug((string) $name);

        if ($baseSlug === '') {
            $baseSlug = 'product';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            DB::table('products')
                ->where('slug', $slug)
                ->where('id', '!=', $productId)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
};
