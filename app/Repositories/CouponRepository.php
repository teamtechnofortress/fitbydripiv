<?php

namespace App\Repositories;

use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CouponRepository
{
    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function findById(string $couponId): Coupon
    {
        return $this->baseQuery()
            ->where('id', $couponId)
            ->firstOrFail();
    }

    public function create(array $data): Coupon
    {
        return DB::transaction(function () use ($data) {
            $coupon = new Coupon();
            $coupon->fill($this->couponAttributes($data));
            $coupon->save();

            $this->syncProducts($coupon, $data);

            return $this->findById($coupon->id);
        });
    }

    public function update(Coupon|string $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($coupon, $data) {
            $coupon = $coupon instanceof Coupon ? $coupon : Coupon::findOrFail($coupon);
            $coupon->fill($this->couponAttributes($data));
            $coupon->save();

            $this->syncProducts($coupon, $data);

            return $this->findById($coupon->id);
        });
    }

    public function delete(Coupon|string $coupon): void
    {
        $coupon = $coupon instanceof Coupon ? $coupon : Coupon::findOrFail($coupon);

        DB::transaction(function () use ($coupon) {
            $coupon->products()->sync([]);
            $coupon->delete();
        });
    }

    public function toggleActive(Coupon|string $coupon): Coupon
    {
        return DB::transaction(function () use ($coupon) {
            $coupon = $coupon instanceof Coupon ? $coupon : Coupon::findOrFail($coupon);
            $coupon->is_active = ! $coupon->is_active;
            $coupon->save();

            return $this->findById($coupon->id);
        });
    }

    protected function baseQuery(array $filters = [])
    {
        $query = Coupon::query()
            ->select([
                'id',
                'code',
                'name',
                'description',
                'type',
                'value',
                'scope',
                'is_active',
                'starts_at',
                'expires_at',
                'usage_limit_total',
                'usage_limit_per_user',
                'applies_to',
                'first_order_only',
                'min_order_amount',
                'max_discount_amount',
                'created_at',
                'updated_at',
            ])
            ->with([
                'products:id,name,slug',
            ])
            ->withCount(['products', 'redemptions']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (! empty($filters['scope'])) {
            $query->where('scope', $filters['scope']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['applies_to'])) {
            $query->where('applies_to', $filters['applies_to']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE));
        }

        return $query;
    }

    protected function couponAttributes(array $data): array
    {
        return [
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'value' => $data['value'],
            'scope' => $data['scope'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'usage_limit_total' => $data['usage_limit_total'] ?? null,
            'usage_limit_per_user' => $data['usage_limit_per_user'] ?? null,
            'applies_to' => $data['applies_to'],
            'first_order_only' => (bool) ($data['first_order_only'] ?? false),
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'max_discount_amount' => $data['max_discount_amount'] ?? null,
        ];
    }

    protected function syncProducts(Coupon $coupon, array $data): void
    {
        if (($data['scope'] ?? null) !== 'product_specific') {
            $coupon->products()->sync([]);

            return;
        }

        $productIds = collect($data['product_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $coupon->products()->sync($productIds);
    }
}
