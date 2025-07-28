<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecipeReviewController extends Controller
{
    public function __construct()
    {
        // 認証が必要なメソッドのみにミドルウェアを適用
        $this->middleware('auth:api')->except(['index', 'show', 'statistics']);
    }

    /**
     * レシピのレビュー一覧取得
     */
    public function index(Recipe $recipe)
    {
        $reviews = $recipe->reviews()
            ->with(['user:id,name', 'creator:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'taste_score' => $review->taste_score,
                    'difficulty_score' => $review->difficulty_score,
                    'instruction_clarity' => $review->instruction_clarity,
                    'comment' => $review->comment,
                    'review_images' => $review->review_images,
                    'average_score' => $review->average_score,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                    ],
                    'created_at' => $review->created_at,
                ];
            }),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * レビュー投稿
     */
    public function store(Request $request, Recipe $recipe)
    {
        $userId = Auth::id();

        // バリデーション
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'taste_score' => 'nullable|integer|min:1|max:5',
            'difficulty_score' => 'nullable|integer|min:1|max:5',
            'instruction_clarity' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'review_images' => 'nullable|array|max:5',
            'review_images.*' => 'string|url',
        ]);

        // 既存のレビューチェック（1ユーザー1レシピ1レビュー）
        $existingReview = RecipeReview::where('recipe_id', $recipe->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'このレシピには既にレビューを投稿済みです。',
            ], 422);
        }

        try {
            $review = RecipeReview::create([
                'recipe_id' => $recipe->id,
                'user_id' => $userId,
                'rating' => $validated['rating'],
                'taste_score' => $validated['taste_score'] ?? null,
                'difficulty_score' => $validated['difficulty_score'] ?? null,
                'instruction_clarity' => $validated['instruction_clarity'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'review_images' => $validated['review_images'] ?? null,
            ]);

            $review->load(['user:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'レビューを投稿しました。',
                'data' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'taste_score' => $review->taste_score,
                    'difficulty_score' => $review->difficulty_score,
                    'instruction_clarity' => $review->instruction_clarity,
                    'comment' => $review->comment,
                    'review_images' => $review->review_images,
                    'average_score' => $review->average_score,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                    ],
                    'created_at' => $review->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'レビューの投稿に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * レビュー詳細取得
     */
    public function show(Recipe $recipe, RecipeReview $review)
    {
        // レビューがレシピに属することを確認
        if ($review->recipe_id !== $recipe->id) {
            return response()->json([
                'success' => false,
                'message' => 'レビューが見つかりませんでした。',
            ], 404);
        }

        $review->load(['user:id,name', 'creator:id,name']);

        return response()->json([
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'taste_score' => $review->taste_score,
                'difficulty_score' => $review->difficulty_score,
                'instruction_clarity' => $review->instruction_clarity,
                'comment' => $review->comment,
                'review_images' => $review->review_images,
                'average_score' => $review->average_score,
                'user' => [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ],
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ],
        ]);
    }

    /**
     * レビュー更新
     */
    public function update(Request $request, Recipe $recipe, RecipeReview $review)
    {
        $userId = Auth::id();

        // レビューがレシピに属することを確認
        if ($review->recipe_id !== $recipe->id) {
            return response()->json([
                'success' => false,
                'message' => 'レビューが見つかりませんでした。',
            ], 404);
        }

        // 投稿者本人のみ更新可能
        if ($review->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => '自分のレビューのみ更新できます。',
            ], 403);
        }

        // バリデーション
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'taste_score' => 'nullable|integer|min:1|max:5',
            'difficulty_score' => 'nullable|integer|min:1|max:5',
            'instruction_clarity' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'review_images' => 'nullable|array|max:5',
            'review_images.*' => 'string|url',
        ]);

        try {
            $review->update([
                'rating' => $validated['rating'],
                'taste_score' => $validated['taste_score'] ?? null,
                'difficulty_score' => $validated['difficulty_score'] ?? null,
                'instruction_clarity' => $validated['instruction_clarity'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'review_images' => $validated['review_images'] ?? null,
            ]);

            $review->load(['user:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'レビューを更新しました。',
                'data' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'taste_score' => $review->taste_score,
                    'difficulty_score' => $review->difficulty_score,
                    'instruction_clarity' => $review->instruction_clarity,
                    'comment' => $review->comment,
                    'review_images' => $review->review_images,
                    'average_score' => $review->average_score,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                    ],
                    'updated_at' => $review->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'レビューの更新に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * レビュー削除
     */
    public function destroy(Recipe $recipe, RecipeReview $review)
    {
        $userId = Auth::id();

        // レビューがレシピに属することを確認
        if ($review->recipe_id !== $recipe->id) {
            return response()->json([
                'success' => false,
                'message' => 'レビューが見つかりませんでした。',
            ], 404);
        }

        // 投稿者本人のみ削除可能
        if ($review->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => '自分のレビューのみ削除できます。',
            ], 403);
        }

        try {
            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'レビューを削除しました。',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'レビューの削除に失敗しました。',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * レシピの評価統計情報取得
     */
    public function statistics(Recipe $recipe)
    {
        $reviews = $recipe->reviews();

        $stats = [
            'total_reviews' => $reviews->count(),
            'average_rating' => round($reviews->avg('rating') ?? 0, 1),
            'average_taste_score' => round($reviews->whereNotNull('taste_score')->avg('taste_score') ?? 0, 1),
            'average_difficulty_score' => round($reviews->whereNotNull('difficulty_score')->avg('difficulty_score') ?? 0, 1),
            'average_instruction_clarity' => round($reviews->whereNotNull('instruction_clarity')->avg('instruction_clarity') ?? 0, 1),
            'rating_distribution' => [],
        ];

        // 評価分布を計算
        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->where('rating', $i)->count();
            $stats['rating_distribution'][$i] = [
                'count' => $count,
                'percentage' => $stats['total_reviews'] > 0 ? round(($count / $stats['total_reviews']) * 100, 1) : 0,
            ];
        }

        return response()->json([
            'data' => $stats,
        ]);
    }
}
