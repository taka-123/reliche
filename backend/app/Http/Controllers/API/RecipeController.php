<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeDetailResource;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * 食材のオートコンプリート検索
     */
    public function searchIngredients(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:100',
        ]);

        $query = trim($request->input('q'));

        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        // SQLインジェクション対策としてサニタイズ
        $query = strip_tags($query);

        $ingredients = Ingredient::where('name', 'LIKE', '%'.$query.'%')
            ->limit(5)
            ->get(['id', 'name']);

        return response()->json(['data' => $ingredients]);
    }

    /**
     * レシピ提案機能
     */
    public function suggest(Request $request)
    {
        $userIngredientIds = $request->input('ingredient_ids', []);

        // バリデーション
        $this->validateIngredientIds($userIngredientIds);

        $recipes = Recipe::with(['ingredients'])
            ->get()
            ->map(function ($recipe) use ($userIngredientIds) {
                $missingCount = $this->calculateMissingCount($recipe, $userIngredientIds);

                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'cooking_time' => $recipe->cooking_time,
                    'missing_count' => $missingCount,
                    'status' => $this->getStatusForIngredients($userIngredientIds, $missingCount),
                ];
            })
            ->sortBy(['missing_count', 'cooking_time'])
            ->values();

        return response()->json(['data' => $recipes]);
    }

    /**
     * 全レシピ一覧取得
     */
    public function index(Request $request)
    {
        $recipes = Recipe::with(['ingredients'])
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'cooking_time' => $recipe->cooking_time,
                    'missing_count' => $recipe->ingredients->count(),
                    'status' => '全ての食材が必要',
                ];
            })
            ->sortBy('cooking_time')
            ->values();

        return response()->json(['data' => $recipes]);
    }

    /**
     * レシピ詳細取得
     */
    public function show($id)
    {
        try {
            $recipe = Recipe::with(['ingredients' => function ($query) {
                $query->select('ingredients.id', 'ingredients.name');
            }])->findOrFail($id);

            return new RecipeDetailResource($recipe);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'data' => null,
                'message' => 'レシピが見つかりませんでした',
            ], 404);
        }
    }

    /**
     * 食材IDのバリデーション
     */
    private function validateIngredientIds($userIngredientIds)
    {
        if (! is_array($userIngredientIds)) {
            return response()->json([
                'success' => false,
                'message' => 'ingredient_idsは配列である必要があります。',
            ], 422);
        }

        if (! empty($userIngredientIds)) {
            request()->validate([
                'ingredient_ids' => 'array|max:20',
                'ingredient_ids.*' => 'integer|exists:ingredients,id',
            ]);
        }
    }

    /**
     * 不足食材数を計算
     */
    private function calculateMissingCount($recipe, $userIngredientIds)
    {
        return empty($userIngredientIds)
            ? $recipe->ingredients->count()
            : $recipe->ingredients->whereNotIn('id', $userIngredientIds)->count();
    }

    /**
     * 食材選択状態に応じたステータスメッセージを取得
     */
    private function getStatusForIngredients($userIngredientIds, $missingCount)
    {
        return empty($userIngredientIds)
            ? '全ての食材が必要'
            : $this->getStatus($missingCount);
    }

    /**
     * 不足食材数からステータスメッセージを取得
     */
    private function getStatus($missingCount)
    {
        return match ($missingCount) {
            0 => '手持ち食材でOK！',
            1 => 'あと1品でOK',
            2 => 'あと2品でOK',
            default => "あと{$missingCount}品でOK"
        };
    }
}
