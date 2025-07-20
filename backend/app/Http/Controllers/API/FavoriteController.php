<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧を取得
     * GET /api/favorites
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = min((int) request('per_page', 20), 100); // 最大100件に制限

            // ユーザーのお気に入りを取得（ページネーション付き）
            $favorites = $user->favorites()
                            ->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'お気に入り一覧を取得しました。',
                'data' => $favorites->items(),
                'meta' => [
                    'current_page' => $favorites->currentPage(),
                    'per_page' => $favorites->perPage(),
                    'total' => $favorites->total(),
                    'last_page' => $favorites->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('お気に入り一覧取得エラー: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'お気に入り一覧の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * お気に入りに追加
     * POST /api/favorites
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // バリデーション
            $validator = Validator::make($request->all(), [
                'recipe_id' => [
                    'required',
                    'integer',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        // 将来的にRecipeモデルができたら存在確認を有効化
                        // if (!Recipe::where('id', $value)->exists()) {
                        //     $fail('指定されたレシピが見つかりません。');
                        // }
                    },
                ],
            ], [
                'recipe_id.required' => 'レシピIDは必須です。',
                'recipe_id.integer' => 'レシピIDは整数である必要があります。',
                'recipe_id.min' => 'レシピIDは1以上である必要があります。',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました。',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $recipeId = (int) $request->input('recipe_id');

            // 既にお気に入りに登録されているかチェック
            if ($user->hasFavorited($recipeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'このレシピは既にお気に入りに登録されています。',
                ], 409);
            }

            // お気に入りに追加
            $favorite = DB::transaction(function () use ($user, $recipeId) {
                return $user->favorites()->create([
                    'recipe_id' => $recipeId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'お気に入りに追加しました。',
                'data' => $favorite,
            ], 201);

        } catch (\Exception $e) {
            Log::error('お気に入り追加エラー: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの追加に失敗しました。',
            ], 500);
        }
    }

    /**
     * お気に入りを削除
     * DELETE /api/favorites/{recipe_id}
     */
    public function destroy(int $recipeId): JsonResponse
    {
        try {
            $user = Auth::user();

            // お気に入りを検索
            $favorite = $user->favorites()->where('recipe_id', $recipeId)->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'お気に入りが見つかりませんでした。',
                ], 404);
            }

            // お気に入りを削除
            DB::transaction(function () use ($favorite) {
                $favorite->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'お気に入りを削除しました。',
            ]);

        } catch (\Exception $e) {
            Log::error('お気に入り削除エラー: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * 特定のレシピがお気に入りかどうかチェック
     * GET /api/favorites/check/{recipe_id}
     */
    public function check(int $recipeId): JsonResponse
    {
        try {
            $user = Auth::user();
            $isFavorited = $user->hasFavorited($recipeId);

            return response()->json([
                'success' => true,
                'message' => 'お気に入り状態を取得しました。',
                'data' => [
                    'recipe_id' => $recipeId,
                    'is_favorited' => $isFavorited,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('お気に入り状態チェックエラー: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'お気に入り状態の取得に失敗しました。',
            ], 500);
        }
    }
}
