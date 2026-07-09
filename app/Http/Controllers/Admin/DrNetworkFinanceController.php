<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DrNetwork;
use App\Models\DrNetworkPayout;
use App\Models\DrNetworkTransaction;
use App\Services\DrNetwork\Finance\DrNetworkFinanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DrNetworkFinanceController extends Controller
{
    private const READ_ROLES = ['admin', 'super_admin', 'network_admin', 'support'];

    private const WRITE_ROLES = ['admin', 'super_admin'];

    public function __construct(
        private DrNetworkFinanceService $financeService,
    ) {}

    public function summary(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
        ]);

        return response()->json($this->financeService->summary(
            $network->id,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null
        ));
    }

    public function transactions(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(DrNetworkTransaction::STATUSES)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DrNetworkTransaction::query()
            ->forNetwork($network->id)
            ->with([
                'order:id,order_uuid,state_code,product_id',
                'order.product:id,name,slug',
                'consultationRecord:id,network_case_id,network_status,internal_status,submitted_at',
                'flow:id,flow_key,name',
                'voidedBy:id,firstName,lastName,email',
            ])
            ->when(isset($validated['status']), fn (Builder $query) => $query->where('status', $validated['status']))
            ->between($validated['date_from'] ?? null, $validated['date_to'] ?? null)
            ->latest('occurred_at');

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function voidTransaction(Request $request, DrNetwork $network, DrNetworkTransaction $transaction): JsonResponse
    {
        $this->authorizeFinanceWrite($request);

        if ((int) $transaction->dr_network_id !== (int) $network->id) {
            abort(404);
        }

        if ($transaction->status !== DrNetworkTransaction::STATUS_ACTIVE) {
            return response()->json(['message' => 'Only active transactions can be voided.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $transaction->loadMissing('order');
        $this->financeService->voidTransactionForOrder($transaction->order, $validated['reason'], $request->user()->id);

        return response()->json($transaction->refresh()->load([
            'order:id,order_uuid,state_code,product_id',
            'order.product:id,name,slug',
            'voidedBy:id,firstName,lastName,email',
        ]));
    }

    public function payouts(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeRead($request);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(DrNetworkPayout::STATUSES)],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DrNetworkPayout::query()
            ->forNetwork($network->id)
            ->with('initiator:id,firstName,lastName,email')
            ->when(isset($validated['status']), fn (Builder $query) => $query->where('status', $validated['status']))
            ->between($validated['date_from'] ?? null, $validated['date_to'] ?? null)
            ->latest('paid_at')
            ->latest('id');

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function storePayout(Request $request, DrNetwork $network): JsonResponse
    {
        $this->authorizeFinanceWrite($request);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'method' => ['required', Rule::in(DrNetworkPayout::METHODS)],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(DrNetworkPayout::STATUSES)],
            'paid_at' => ['sometimes', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        $payout = $this->financeService->recordPayout($network->id, $validated, $request->user()->id);

        return response()->json($payout->load('initiator:id,firstName,lastName,email'), 201);
    }

    private function authorizeRead(Request $request): void
    {
        $this->authorizeRole($request, self::READ_ROLES);
    }

    private function authorizeFinanceWrite(Request $request): void
    {
        $this->authorizeRole($request, self::WRITE_ROLES);
    }

    private function authorizeRole(Request $request, array $roles): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (in_array($user->role, $roles, true) || ((bool) ($user->isCompany ?? false) && in_array('admin', $roles, true))) {
            return;
        }

        abort(response()->json(['message' => 'You do not have permission to manage Dr Network finance.'], 403));
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 25), 1), 100);
    }
}
