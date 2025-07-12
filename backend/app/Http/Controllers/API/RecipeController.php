<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\RecipeDetailResource;

class RecipeController extends Controller
{
    /**
     * 食材のオートコンプリート検索
     */
    public function searchIngredients(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:100'
        ]);

        $query = trim($request->input('q'));
        
        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        // SQLインジェクション対策としてサニタイズ
        $query = strip_tags($query);
        
        $ingredients = Ingredient::where('name', 'LIKE', '%' . $query . '%')
                                ->limit(5)
                                ->get(['id', 'name']);

        return response()->json(['data' => $ingredients]);
    }

    /**
     * レシピ提案機能
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'ingredient_ids' => 'required|array|min:1|max:20',
            'ingredient_ids.*' => 'integer|exists:ingredients,id'
        ]);

        $userIngredientIds = $request->input('ingredient_ids', []);

        $recipes = Recipe::with(['ingredients'])
            ->get()
            ->map(function ($recipe) use ($userIngredientIds) {
                $missingCount = $recipe->ingredients
                    ->whereNotIn('id', $userIngredientIds)
                    ->count();

                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'cooking_time' => $recipe->cooking_time,
                    'missing_count' => $missingCount,
                    'status' => $this->getStatus($missingCount),
                ];
            })
            ->sortBy(['missing_count', 'cooking_time'])
            ->values();

        return response()->json(['data' => $recipes]);
    }

    /**
     * レシピ詳細取得
     */
    public function show($id)
    {
        try {
            $recipe = Recipe::with(['ingredients' => function($query) {
                $query->select('ingredients.id', 'ingredients.name');
            }])->findOrFail($id);
            return new RecipeDetailResource($recipe);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'data' => null,
                'message' => 'レシピが見つかりませんでした'
            ], 404);
        }
    }

    /**
     * 不足食材数からステータスメッセージを取得
     */
    private function getStatus($missingCount)
    {
        return match($missingCount) {
            0 => '手持ち食材でOK！',
            1 => 'あと1品でOK',
            2 => 'あと2品でOK',
            default => "あと{$missingCount}品でOK"
        };
    }
}
