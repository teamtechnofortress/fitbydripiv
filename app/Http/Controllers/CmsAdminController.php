<?php

namespace App\Http\Controllers;

use App\Models\CmsCategory;
use App\Models\CmsContactSubmission;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\CmsSiteSetting;

class CmsAdminController extends Controller
{
    public function getOrderStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'product_id' => ['sometimes', 'nullable', 'uuid', 'exists:products,id'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'payment_status' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $baseQuery = $this->filteredOrdersQuery($validated);

        $summary = $this->orderStatsSummary(clone $baseQuery);
        $totalSold = (int) $summary['sold_orders'];
        $productStats = $this->productOrderStats(clone $baseQuery, $totalSold);

        return response()->json([
            'success' => true,
            'data' => [
                'filters' => [
                    'date_from' => $validated['date_from'] ?? null,
                    'date_to' => $validated['date_to'] ?? null,
                    'product_id' => $validated['product_id'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'payment_status' => $validated['payment_status'] ?? null,
                ],
                'summary' => $summary,
                'status_counts' => $this->groupedOrderCounts(clone $baseQuery, 'status'),
                'payment_status_counts' => $this->groupedOrderCounts(clone $baseQuery, 'payment_status'),
                'product_sales' => $productStats,
                'chart' => [
                    'labels' => $productStats->pluck('product_name')->values(),
                    'sold_counts' => $productStats->pluck('sold_orders')->values(),
                    'percentages' => $productStats->pluck('sold_percentage')->values(),
                ],
            ],
        ]);
    }

    // Categories
    public function getCategories(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => CmsCategory::orderBy('display_order')->get()]);
    }

    public function saveCategory(Request $request): JsonResponse
    {
        $categoryId = $request->input('id');

        $validated = $request->validate([
            'id' => 'sometimes|uuid',
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string',
            'landscape_banner' => 'nullable|string',
            'background_video' => 'nullable|string',
            'video_playback_speed' => 'nullable|numeric|min:0.5|max:3.0',
            'display_order' => [
                'nullable',
                'integer',
                Rule::unique('cms_categories', 'display_order')->ignore($categoryId),
            ],
        ], [
            'display_order.unique' => 'Sort order must be unique. This value is already in use.',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = isset($validated['id'])
            ? CmsCategory::findOrFail($validated['id'])
            : new CmsCategory();

        $category->fill($validated);
        $category->save();

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function deleteCategory(string $id): JsonResponse
    {
        CmsCategory::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted']);
    }
    // Site Settings
    public function saveSiteSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hero_video_url' => 'nullable|string',
            'hero_poster_image' => 'nullable|string',
            'hero_video_playback_speed' => 'nullable|numeric|min:0.5|max:3.0',
        ]);

        $settings = CmsSiteSetting::firstOrCreate(['id' => 1]);
        $settings->update($validated);

        return response()->json(['success' => true, 'data' => $settings]);
    }

    // Contact Submissions
    public function getContactSubmissions(Request $request): JsonResponse
    {
        $query = CmsContactSubmission::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['success' => true, 'data' => $query->paginate(20)]);
    }

    public function updateContactStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['status' => 'required|string|in:new,read,replied,archived']);
        $submission = CmsContactSubmission::findOrFail($id);
        $submission->update($validated);
        return response()->json(['success' => true, 'data' => $submission]);
    }

    private function filteredOrdersQuery(array $filters)
    {
        return Order::query()
            ->when(! empty($filters['date_from']), fn ($query) => $query->where('orders.created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->where('orders.created_at', '<=', $filters['date_to']))
            ->when(! empty($filters['product_id']), fn ($query) => $query->where('orders.product_id', $filters['product_id']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('orders.status', $filters['status']))
            ->when(! empty($filters['payment_status']), fn ($query) => $query->where('orders.payment_status', $filters['payment_status']));
    }

    private function orderStatsSummary($query): array
    {
        $row = $query
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN orders.status = 'completed' THEN 1 ELSE 0 END) as completed_orders")
            ->selectRaw("SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            ->selectRaw("SUM(CASE WHEN orders.payment_status = 'failed' THEN 1 ELSE 0 END) as failed_orders")
            ->selectRaw("SUM(CASE WHEN orders.payment_status = 'paid' OR orders.status = 'completed' THEN 1 ELSE 0 END) as sold_orders")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.payment_status = 'paid' OR orders.status = 'completed' THEN COALESCE(orders.final_amount, orders.price, 0) ELSE 0 END), 0) as gross_revenue")
            ->first();

        return [
            'total_orders' => (int) ($row->total_orders ?? 0),
            'created_orders' => (int) ($row->total_orders ?? 0),
            'completed_orders' => (int) ($row->completed_orders ?? 0),
            'pending_orders' => (int) ($row->pending_orders ?? 0),
            'failed_orders' => (int) ($row->failed_orders ?? 0),
            'cancelled_orders' => (int) ($row->cancelled_orders ?? 0),
            'sold_orders' => (int) ($row->sold_orders ?? 0),
            'gross_revenue' => number_format((float) ($row->gross_revenue ?? 0), 2, '.', ''),
        ];
    }

    private function groupedOrderCounts($query, string $column)
    {
        return $query
            ->select("orders.{$column} as key")
            ->selectRaw('COUNT(*) as count')
            ->groupBy("orders.{$column}")
            ->orderBy("orders.{$column}")
            ->get()
            ->map(fn ($row): array => [
                'key' => $row->key ?? 'unknown',
                'label' => Str::of($row->key ?? 'unknown')->replace('_', ' ')->title()->toString(),
                'count' => (int) $row->count,
            ])
            ->values();
    }

    private function productOrderStats($query, int $totalSold)
    {
        return $query
            ->leftJoin('products', 'products.id', '=', 'orders.product_id')
            ->select('orders.product_id')
            ->selectRaw("COALESCE(products.name, 'Unknown Product') as product_name")
            ->selectRaw('products.slug as product_slug')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN orders.status = 'completed' THEN 1 ELSE 0 END) as completed_orders")
            ->selectRaw("SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN orders.payment_status = 'failed' THEN 1 ELSE 0 END) as failed_orders")
            ->selectRaw("SUM(CASE WHEN orders.payment_status = 'paid' OR orders.status = 'completed' THEN 1 ELSE 0 END) as sold_orders")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.payment_status = 'paid' OR orders.status = 'completed' THEN COALESCE(orders.final_amount, orders.price, 0) ELSE 0 END), 0) as gross_revenue")
            ->groupBy('orders.product_id', 'products.name', 'products.slug')
            ->orderByDesc('sold_orders')
            ->orderBy('product_name')
            ->get()
            ->map(fn ($row): array => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'product_slug' => $row->product_slug,
                'total_orders' => (int) $row->total_orders,
                'completed_orders' => (int) $row->completed_orders,
                'pending_orders' => (int) $row->pending_orders,
                'failed_orders' => (int) $row->failed_orders,
                'sold_orders' => (int) $row->sold_orders,
                'sold_percentage' => $totalSold > 0
                    ? round(((int) $row->sold_orders / $totalSold) * 100, 2)
                    : 0.0,
                'gross_revenue' => number_format((float) $row->gross_revenue, 2, '.', ''),
            ])
            ->values();
    }
}
