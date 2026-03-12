<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'profile_step')) {
                $table->unsignedTinyInteger('profile_step')
                    ->default(2)
                    ->after('require_signature');
            }

            if (! Schema::hasColumn('users', 'profile_completed_at')) {
                $table->timestamp('profile_completed_at')
                    ->nullable()
                    ->after('profile_step');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            foreach (['profile_step', 'profile_completed_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
