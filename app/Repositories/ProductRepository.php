<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function findById(string $productId): Product
    {
        return Product::findOrFail($productId);
    }

    public function findListItemById(string $productId): Product
    {
        return $this->baseQuery()
            ->where('id', $productId)
            ->firstOrFail();
    }

    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function paginateDrafts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->where('is_published', false)
            ->whereIn('completion_status', ['draft', 'incomplete'])
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function searchForSelection(string $search, int $limit = 5): Collection
    {
        $search = trim($search);

        return Product::query()
            ->select([
                'id',
                'name',
                'slug',
            ])
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            })
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN slug LIKE ? THEN 1 ELSE 2 END', [
                $search . '%',
                $search . '%',
            ])
            ->orderBy('name')
            ->limit(max(1, min($limit, 20)))
            ->get();
    }

    protected function baseQuery(array $filters = [])
    {
        $query = Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'category',
                'description',
                'is_featured',
                'is_published',
                'completion_status',
                'completion_percentage',
                'completion_step',
                'cover_image_id',
                'created_at',
                'updated_at',
            ])
            ->with(['coverImage'])
            ->withCount(['images', 'benefits', 'faqs', 'researchLinks', 'pricing']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (array_key_exists('is_published', $filters) && $filters['is_published'] !== null) {
            $query->where('is_published', filter_var($filters['is_published'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE));
        }

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== null) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE));
        }

        return $query;
    }
}
