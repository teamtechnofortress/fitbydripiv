<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Repositories\CouponRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCouponController extends Controller
{
    public function __construct(
        protected CouponRepository $couponRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'scope' => ['nullable', Rule::in(['global', 'product_specific'])],
            'type' => ['nullable', Rule::in(['percent', 'fixed'])],
            'applies_to' => ['nullable', Rule::in(['all', 'one_time', 'subscription'])],
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $coupons = $this->couponRepository->paginateAll(
            $validated,
            $validated['per_page'] ?? 15
        );

        return response()->json([
            'success' => true,
            'data' => collect($coupons->items())
                ->map(fn (Coupon $coupon) => $this->transformCoupon($coupon))
                ->values()
                ->all(),
            'meta' => $this->paginationMeta($coupons),
        ]);
    }

    public function show(string $couponId): JsonResponse
    {
        $coupon = $this->couponRepository->findById($couponId);

        return response()->json([
            'success' => true,
            'data' => $this->transformCoupon($coupon),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCouponRequest($request);
        $coupon = $this->couponRepository->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully.',
            'data' => $this->transformCoupon($coupon),
        ], 201);
    }

    public function update(Request $request, string $couponId): JsonResponse
    {
        $coupon = $this->couponRepository->findById($couponId);
        $validated = $this->validateCouponRequest($request, $coupon);
        $coupon = $this->couponRepository->update($coupon, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully.',
            'data' => $this->transformCoupon($coupon),
        ]);
    }

    public function destroy(string $couponId): JsonResponse
    {
        $coupon = $this->couponRepository->findById($couponId);
        $this->couponRepository->delete($coupon);

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully.',
            'data' => [
                'coupon_id' => $couponId,
            ],
        ]);
    }

    public function toggleActive(string $couponId): JsonResponse
    {
        $coupon = $this->couponRepository->toggleActive($couponId);

        return response()->json([
            'success' => true,
            'message' => $coupon->is_active
                ? 'Coupon activated successfully.'
                : 'Coupon deactivated successfully.',
            'data' => [
                'coupon_id' => $coupon->id,
                'is_active' => $coupon->is_active,
                'status' => $coupon->is_active ? 'active' : 'inactive',
                'coupon' => $this->transformCoupon($coupon),
            ],
        ]);
    }

    protected function validateCouponRequest(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => 'required|numeric|min:0.01',
            'scope' => ['required', Rule::in(['global', 'product_specific'])],
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'applies_to' => ['required', Rule::in(['all', 'one_time', 'subscription'])],
            'first_order_only' => 'nullable|boolean',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'string|exists:products,id',
        ]);

        if ($validated['type'] === 'percent' && (float) $validated['value'] > 100) {
            throw ValidationException::withMessages([
                'value' => 'Percent coupon value cannot exceed 100.',
            ]);
        }

        if (
            $validated['scope'] === 'product_specific'
            && count($validated['product_ids'] ?? []) === 0
        ) {
            throw ValidationException::withMessages([
                'product_ids' => 'Product-specific coupons require at least one product.',
            ]);
        }

        return $validated;
    }

    protected function transformCoupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'description' => $coupon->description,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'scope' => $coupon->scope,
            'is_active' => $coupon->is_active,
            'starts_at' => $coupon->starts_at,
            'expires_at' => $coupon->expires_at,
            'usage_limit_total' => $coupon->usage_limit_total,
            'usage_limit_per_user' => $coupon->usage_limit_per_user,
            'applies_to' => $coupon->applies_to,
            'first_order_only' => $coupon->first_order_only,
            'min_order_amount' => $coupon->min_order_amount,
            'max_discount_amount' => $coupon->max_discount_amount,
            'products_count' => $coupon->products_count ?? $coupon->products->count(),
            'redemptions_count' => $coupon->redemptions_count ?? $coupon->redemptions->count(),
            'products' => $coupon->products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ])->values()->all(),
            'created_at' => $coupon->created_at,
            'updated_at' => $coupon->updated_at,
        ];
    }

    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
