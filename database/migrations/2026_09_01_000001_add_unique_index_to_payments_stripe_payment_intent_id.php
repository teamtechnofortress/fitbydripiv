<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('payments')
            ->where('stripe_payment_intent_id', '')
            ->update(['stripe_payment_intent_id' => null]);

        $duplicateIntentIds = DB::table('payments')
            ->select('stripe_payment_intent_id')
            ->whereNotNull('stripe_payment_intent_id')
            ->groupBy('stripe_payment_intent_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('stripe_payment_intent_id');

        foreach ($duplicateIntentIds as $duplicateIntentId) {
            $payments = DB::table('payments')
                ->where('stripe_payment_intent_id', $duplicateIntentId)
                ->orderByRaw("CASE WHEN status = 'paid' THEN 0 WHEN status = 'failed' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->get(['id']);

            $keeperId = $payments->first()?->id;

            if (! $keeperId) {
                continue;
            }

            $duplicateIds = $payments
                ->pluck('id')
                ->filter(fn ($id): bool => (int) $id !== (int) $keeperId)
                ->values();

            if ($duplicateIds->isEmpty()) {
                continue;
            }

            DB::table('stripe_webhook_events')
                ->whereIn('webhookable_type', ['payments', 'App\\Models\\Payment'])
                ->whereIn('webhookable_id', $duplicateIds)
                ->update(['webhookable_id' => $keeperId]);

            DB::table('payments')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique('stripe_payment_intent_id', 'payments_stripe_payment_intent_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_stripe_payment_intent_id_unique');
        });
    }
};
