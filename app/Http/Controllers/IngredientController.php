<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $limit = $validated['limit'] ?? 10;

        $query = Ingredient::query()->orderBy('name');

        if (! empty($validated['search'])) {
            $query->where('name', 'like', '%' . $validated['search'] . '%');
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit($limit)->get(['id', 'name', 'description']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $ingredient = Ingredient::firstOrCreate(
            ['name' => $validated['name']],
            ['description' => $validated['description'] ?? null]
        );

        if (! $ingredient->wasRecentlyCreated && array_key_exists('description', $validated) && empty($ingredient->description)) {
            $ingredient->description = $validated['description'];
            $ingredient->save();
        }

        return response()->json([
            'success' => true,
            'data' => $ingredient,
        ]);
    }
}
