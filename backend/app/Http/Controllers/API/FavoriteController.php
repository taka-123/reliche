<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Create a new FavoriteController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * ユーザーのお気に入りレシピ一覧を取得
     */
    public function index(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        $favorites = $user->favoriteRecipes()
            ->with(['ingredients'])
            ->paginate(15);

        return response()->json([
            'data' => $favorites->items(),
            'current_page' => $favorites->currentPage(),
            'last_page' => $favorites->lastPage(),
            'total' => $favorites->total(),
        ]);
    }

    /**
     * レシピをお気に入りに追加
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'recipe_id' => 'required|integer|exists:recipes,id',
        ]);

        $user = Auth::guard('api')->user();
        $recipeId = $request->recipe_id;

        // 既にお気に入りに追加されているかチェック
        $existingFavorite = Favorite::where('user_id', $user->id)
            ->where('recipe_id', $recipeId)
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'message' => 'このレシピは既にお気に入りに追加されています',
                'is_favorited' => true,
            ], 409);
        }

        // お気に入りに追加
        Favorite::create([
            'user_id' => $user->id,
            'recipe_id' => $recipeId,
        ]);

        return response()->json([
            'message' => 'お気に入りに追加しました',
            'is_favorited' => true,
        ], 201);
    }

    /**
     * レシピをお気に入りから削除
     */
    public function destroy(int $recipeId): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('recipe_id', $recipeId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'message' => 'このレシピはお気に入りに追加されていません',
                'is_favorited' => false,
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'お気に入りから削除しました',
            'is_favorited' => false,
        ]);
    }

    /**
     * レシピのお気に入り状態を確認
     */
    public function check(int $recipeId): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $isFavorited = Favorite::where('user_id', $user->id)
            ->where('recipe_id', $recipeId)
            ->exists();

        return response()->json([
            'is_favorited' => $isFavorited,
        ]);
    }
}