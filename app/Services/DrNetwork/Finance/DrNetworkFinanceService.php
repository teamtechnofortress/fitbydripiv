<?php

namespace App\Services\DrNetwork\Finance;

use App\Models\ConsultationRecord;
use App\Models\DrNetworkPayout;
use App\Models\DrNetworkTransaction;
use App\Models\Order;
use App\Support\Money\DecimalMoney;
use Illuminate\Support\Facades\DB;

class DrNetworkFinanceService
{
    public function recordTransactionForSubmission(Order $order, ConsultationRecord $record): DrNetworkTransaction
    {
        $payload = [
            'dr_network_id' => $order->dr_network_id,
            'consultation_record_id' => $record->id,
            'product_id' => $order->product_id,
            'flow_id' => $order->network_flow_id,
            'patient_paid_amount' => $order->dr_network_patient_fee_amount ?? 0,
            'network_owed_amount' => $order->dr_network_fee_amount ?? 0,
            'currency' => $order->currency ?? 'USD',
            'status' => DrNetworkTransaction::STATUS_ACTIVE,
            'void_reason' => null,
            'voided_by' => null,
            'voided_at' => null,
            'occurred_at' => $record->submitted_at ?? now(),
            'metadata' => [
                'network_case_id' => $record->network_case_id,
                'order_uuid' => $order->order_uuid,
            ],
        ];

        $transaction = DrNetworkTransaction::query()
            ->where('order_id', $order->id)
            ->first();

        if ($transaction) {
            if ($transaction->status !== DrNetworkTransaction::STATUS_ACTIVE) {
                return $transaction;
            }

            $transaction->update($payload);

            return $transaction->refresh();
        }

        return DrNetworkTransaction::query()->create(array_merge([
            'order_id' => $order->id,
        ], $payload));
    }

    public function voidTransactionForOrder(Order $order, string $reason, int $actorId): void
    {
        DrNetworkTransaction::query()
            ->where('order_id', $order->id)
            ->update([
                'status' => DrNetworkTransaction::STATUS_VOID,
                'void_reason' => $reason,
                'voided_by' => $actorId,
                'voided_at' => now(),
            ]);
    }

    public function summary(int $networkId, ?string $from = null, ?string $to = null): array
    {
        $totalPatientPaid = DrNetworkTransaction::query()
            ->forNetwork($networkId)
            ->countsTowardPatientRevenue()
            ->between($from, $to)
            ->sum('patient_paid_amount');

        $totalNetworkOwed = DrNetworkTransaction::query()
            ->forNetwork($networkId)
            ->countsTowardNetworkObligation()
            ->between($from, $to)
            ->sum('network_owed_amount');

        $transactionCount = DrNetworkTransaction::query()
            ->forNetwork($networkId)
            ->countsTowardNetworkObligation()
            ->between($from, $to)
            ->count();

        $paidOut = DrNetworkPayout::query()
            ->forNetwork($networkId)
            ->countsTowardPaidOut()
            ->between($from, $to)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_paid_out')
            ->selectRaw('COUNT(*) as payout_count')
            ->first();

        $allTimeOwed = DrNetworkTransaction::query()
            ->forNetwork($networkId)
            ->countsTowardNetworkObligation()
            ->sum('network_owed_amount');

        $allTimePaidOut = DrNetworkPayout::query()
            ->forNetwork($networkId)
            ->countsTowardPaidOut()
            ->sum('amount');

        return [
            'total_patient_paid' => $this->money($totalPatientPaid),
            'total_network_owed' => $this->money($totalNetworkOwed),
            'profit' => DecimalMoney::subtract($totalPatientPaid, $totalNetworkOwed),
            'total_paid_out' => $this->money($paidOut->total_paid_out ?? 0),
            'remaining_balance' => DecimalMoney::subtract($allTimeOwed, $allTimePaidOut),
            'transaction_count' => $transactionCount,
            'payout_count' => (int) ($paidOut->payout_count ?? 0),
        ];
    }

    public function recordPayout(int $networkId, array $data, int $actorId): DrNetworkPayout
    {
        return DB::transaction(fn (): DrNetworkPayout => DrNetworkPayout::query()->create([
            'dr_network_id' => $networkId,
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'method' => $data['method'],
            'reference_number' => $data['reference_number'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => $data['status'] ?? DrNetworkPayout::STATUS_COMPLETED,
            'paid_at' => $data['paid_at'] ?? now(),
            'initiated_by' => $actorId,
            'metadata' => $data['metadata'] ?? null,
        ]));
    }

    private function money(mixed $value): string
    {
        return DecimalMoney::normalize($value);
    }
}
