<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `stripe_webhook_events` MODIFY `webhookable_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `stripe_webhook_events` MODIFY `webhookable_type` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE `stripe_webhook_events` SET `webhookable_id` = 0 WHERE `webhookable_id` IS NULL');
        DB::statement("UPDATE `stripe_webhook_events` SET `webhookable_type` = '' WHERE `webhookable_type` IS NULL");
        DB::statement('ALTER TABLE `stripe_webhook_events` MODIFY `webhookable_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `stripe_webhook_events` MODIFY `webhookable_type` VARCHAR(255) NOT NULL');
    }
};
