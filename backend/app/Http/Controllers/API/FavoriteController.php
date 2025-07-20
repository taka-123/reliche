<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the user's favorite recipes.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $favorites = Favorite::with('recipe')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites->map(function ($favorite) {
                return [
                    'id' => $favorite->id,
                    'recipe' => [
                        'id' => $favorite->recipe->id,
                        'name' => $favorite->recipe->name,
                        'description' => $favorite->recipe->description,
                        'instructions' => $favorite->recipe->instructions,
                        'cooking_time' => $favorite->recipe->cooking_time,
                        'difficulty' => $favorite->recipe->difficulty,
                        'created_at' => $favorite->recipe->created_at,
                        'updated_at' => $favorite->recipe->updated_at,
                    ],
                    'favorited_at' => $favorite->created_at,
                ];
            })
        ]);
    }

    /**
     * Store a newly created favorite.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'recipe_id' => 'required|integer|exists:recipes,id'
            ]);

            $user = Auth::user();
            $recipeId = $request->recipe_id;

            // 既にお気に入りに追加されているかチェック
            $existingFavorite = Favorite::where('user_id', $user->id)
                ->where('recipe_id', $recipeId)
                ->first();

            if ($existingFavorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'このレシピは既にお気に入りに追加されています'
                ], 409);
            }

            // お気に入りを追加
            $favorite = Favorite::create([
                'user_id' => $user->id,
                'recipe_id' => $recipeId
            ]);

            $favorite->load('recipe');

            return response()->json([
                'success' => true,
                'message' => 'お気に入りに追加しました',
                'data' => [
                    'id' => $favorite->id,
                    'recipe' => [
                        'id' => $favorite->recipe->id,
                        'name' => $favorite->recipe->name,
                        'description' => $favorite->recipe->description,
                        'instructions' => $favorite->recipe->instructions,
                        'cooking_time' => $favorite->recipe->cooking_time,
                        'difficulty' => $favorite->recipe->difficulty,
                    ],
                    'favorited_at' => $favorite->created_at,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの追加に失敗しました'
            ], 500);
        }
    }

    /**
     * Remove the specified favorite.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $favorite = Favorite::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'お気に入りが見つかりません'
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'success' => true,
                'message' => 'お気に入りから削除しました'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの削除に失敗しました'
            ], 500);
        }
    }

    /**
     * Remove favorite by recipe ID.
     */
    public function removeByRecipe(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'recipe_id' => 'required|integer|exists:recipes,id'
            ]);

            $user = Auth::user();
            $recipeId = $request->recipe_id;

            $favorite = Favorite::where('user_id', $user->id)
                ->where('recipe_id', $recipeId)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'お気に入りが見つかりません'
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'success' => true,
                'message' => 'お気に入りから削除しました'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの削除に失敗しました'
            ], 500);
        }
    }

    /**
     * Check if a recipe is favorited by the user.
     */
    public function checkFavorite(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'recipe_id' => 'required|integer|exists:recipes,id'
            ]);

            $user = Auth::user();
            $recipeId = $request->recipe_id;

            $isFavorited = Favorite::where('user_id', $user->id)
                ->where('recipe_id', $recipeId)
                ->exists();

            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入り状態の確認に失敗しました'
            ], 500);
        }
    }
}