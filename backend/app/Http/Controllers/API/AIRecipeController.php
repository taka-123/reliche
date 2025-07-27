<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AIRecipeGeneratorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AIRecipeController extends Controller
{
    private AIRecipeGeneratorService $aiRecipeService;

    public function __construct(AIRecipeGeneratorService $aiRecipeService)
    {
        $this->aiRecipeService = $aiRecipeService;
    }

    /**
     * 基本レシピ生成
     * POST /api/recipes/generate
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category' => 'nullable|string|in:和食,洋食,中華,イタリアン,フレンチ,その他',
                'save_to_db' => 'boolean',
            ]);

            $category = $request->input('category');
            $saveToDb = $request->input('save_to_db', false);

            $recipeData = $this->aiRecipeService->generateBasicRecipe($category);

            $response = [
                'success' => true,
                'data' => $recipeData,
                'message' => 'レシピを生成しました',
            ];

            if ($saveToDb) {
                $recipe = $this->aiRecipeService->saveRecipe($recipeData);
                $response['saved_recipe_id'] = $recipe->id;
                $response['message'] = 'レシピを生成し、データベースに保存しました';
            }

            return response()->json($response);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('AI recipe generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'レシピの生成に失敗しました',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 食材指定レシピ生成
     * POST /api/recipes/generate/ingredients
     */
    public function generateByIngredients(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ingredients' => 'required|array|min:1|max:10',
                'ingredients.*' => 'required|string|max:100',
                'save_to_db' => 'boolean',
            ]);

            $ingredients = $request->input('ingredients');
            $saveToDb = $request->input('save_to_db', false);

            $recipeData = $this->aiRecipeService->generateRecipeByIngredients($ingredients);

            $response = [
                'success' => true,
                'data' => $recipeData,
                'message' => '指定食材を使ったレシピを生成しました',
            ];

            if ($saveToDb) {
                $recipe = $this->aiRecipeService->saveRecipe($recipeData);
                $response['saved_recipe_id'] = $recipe->id;
                $response['message'] = '指定食材を使ったレシピを生成し、データベースに保存しました';
            }

            return response()->json($response);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('AI recipe generation by ingredients failed', [
                'ingredients' => $request->input('ingredients', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'レシピの生成に失敗しました',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 制約条件付きレシピ生成
     * POST /api/recipes/generate/constraints
     */
    public function generateWithConstraints(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'max_time' => 'nullable|integer|min:5|max:120',
                'tags' => 'nullable|array|max:5',
                'tags.*' => 'string|in:時短,節約,ヘルシー,簡単,ボリューム,おつまみ,デザート',
                'difficulty' => 'nullable|string|in:簡単,普通,難しい',
                'save_to_db' => 'boolean',
            ]);

            $constraints = array_filter([
                'max_time' => $request->input('max_time'),
                'tags' => $request->input('tags'),
                'difficulty' => $request->input('difficulty'),
            ]);

            $saveToDb = $request->input('save_to_db', false);

            $recipeData = $this->aiRecipeService->generateRecipeWithConstraints($constraints);

            $response = [
                'success' => true,
                'data' => $recipeData,
                'message' => '制約条件を満たすレシピを生成しました',
            ];

            if ($saveToDb) {
                $recipe = $this->aiRecipeService->saveRecipe($recipeData);
                $response['saved_recipe_id'] = $recipe->id;
                $response['message'] = '制約条件を満たすレシピを生成し、データベースに保存しました';
            }

            return response()->json($response);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('AI recipe generation with constraints failed', [
                'constraints' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'レシピの生成に失敗しました',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
