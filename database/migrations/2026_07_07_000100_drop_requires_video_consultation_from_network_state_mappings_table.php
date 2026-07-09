<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('network_state_mappings', 'requires_video_consultation')) {
            return;
        }

        Schema::table('network_state_mappings', function (Blueprint $table): void {
            $table->dropColumn('requires_video_consultation');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('network_state_mappings', 'requires_video_consultation')) {
            return;
        }

        Schema::table('network_state_mappings', function (Blueprint $table): void {
            $table->boolean('requires_video_consultation')->default(false)->after('flow_id');
        });
    }
};
