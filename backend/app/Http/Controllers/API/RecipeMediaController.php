<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RecipeMediaController extends Controller
{
    public function __construct()
    {
        // 認証が必要なメソッドのみにミドルウェアを適用
        $this->middleware('auth:api')->except(['index', 'show']);
    }

    /**
     * レシピのメディア一覧取得
     */
    public function index(Recipe $recipe): JsonResponse
    {
        $media = $recipe->approvedMedia()
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    /**
     * メディア詳細取得
     */
    public function show(Recipe $recipe, RecipeMedia $media): JsonResponse
    {
        if ($media->recipe_id !== $recipe->id) {
            return response()->json(['error' => 'メディアがレシピに関連付けられていません'], 400);
        }

        if (! $media->isApproved()) {
            return response()->json(['error' => 'メディアが承認されていません'], 403);
        }

        $media->load(['user:id,name', 'approver:id,name']);

        return response()->json(['data' => $media]);
    }

    /**
     * メディアアップロード
     */
    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpeg,jpg,png,gif,mp4,mov,avi,webm',
            ],
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'バリデーションエラー',
                'details' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $mediaType = $this->detectMediaType($file);

        // ファイルサイズ制限チェック
        if ($mediaType === 'image' && $file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['error' => '画像ファイルは5MB以下にしてください'], 422);
        }

        if ($mediaType === 'video' && $file->getSize() > 50 * 1024 * 1024) {
            return response()->json(['error' => '動画ファイルは50MB以下にしてください'], 422);
        }

        try {
            // ファイル保存
            $filename = $this->generateUniqueFilename($file);
            $path = "recipe-media/{$recipe->id}/".$filename;

            Storage::disk('public')->putFileAs(
                "recipe-media/{$recipe->id}",
                $file,
                $filename
            );

            // メタデータ取得
            $metadata = $this->extractMetadata($file, $mediaType);

            // 画像の場合は最適化
            if ($mediaType === 'image') {
                $this->optimizeImage(Storage::disk('public')->path($path));
            }

            // データベースに保存
            $media = RecipeMedia::create([
                'recipe_id' => $recipe->id,
                'user_id' => Auth::id(),
                'media_type' => $mediaType,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'metadata' => $metadata,
                'description' => $request->input('description'),
                'is_approved' => false, // 承認待ち状態
            ]);

            $media->load(['user:id,name']);

            return response()->json([
                'message' => 'メディアをアップロードしました。承認後に公開されます。',
                'data' => $media,
            ], 201);

        } catch (\Exception $e) {
            // アップロードに失敗した場合はファイルを削除
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return response()->json([
                'error' => 'ファイルのアップロードに失敗しました',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * メディア更新（説明文のみ）
     */
    public function update(Request $request, Recipe $recipe, RecipeMedia $media): JsonResponse
    {
        if ($media->recipe_id !== $recipe->id) {
            return response()->json(['error' => 'メディアがレシピに関連付けられていません'], 400);
        }

        if ($media->user_id !== Auth::id()) {
            return response()->json(['error' => '権限がありません'], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'バリデーションエラー',
                'details' => $validator->errors(),
            ], 422);
        }

        $media->update([
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'message' => 'メディア情報を更新しました',
            'data' => $media,
        ]);
    }

    /**
     * メディア削除
     */
    public function destroy(Recipe $recipe, RecipeMedia $media): JsonResponse
    {
        if ($media->recipe_id !== $recipe->id) {
            return response()->json(['error' => 'メディアがレシピに関連付けられていません'], 400);
        }

        if ($media->user_id !== Auth::id()) {
            return response()->json(['error' => '権限がありません'], 403);
        }

        // ファイル削除
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        // データベースから削除（論理削除）
        $media->delete();

        return response()->json(['message' => 'メディアを削除しました']);
    }

    /**
     * メディア承認（管理者のみ）
     */
    public function approve(Recipe $recipe, RecipeMedia $media): JsonResponse
    {
        // 簡易的な管理者チェック（実際は適切な権限管理を実装）
        $user = Auth::user();
        if (! $user || $user->email !== 'admin@example.com') {
            return response()->json(['error' => '管理者権限が必要です'], 403);
        }

        if ($media->recipe_id !== $recipe->id) {
            return response()->json(['error' => 'メディアがレシピに関連付けられていません'], 400);
        }

        if ($media->isApproved()) {
            return response()->json(['error' => '既に承認済みです'], 400);
        }

        $media->approve();

        return response()->json([
            'message' => 'メディアを承認しました',
            'data' => $media,
        ]);
    }

    /**
     * メディア承認取り消し（管理者のみ）
     */
    public function unapprove(Recipe $recipe, RecipeMedia $media): JsonResponse
    {
        // 簡易的な管理者チェック（実際は適切な権限管理を実装）
        $user = Auth::user();
        if (! $user || $user->email !== 'admin@example.com') {
            return response()->json(['error' => '管理者権限が必要です'], 403);
        }

        if ($media->recipe_id !== $recipe->id) {
            return response()->json(['error' => 'メディアがレシピに関連付けられていません'], 400);
        }

        if (! $media->isApproved()) {
            return response()->json(['error' => '承認されていません'], 400);
        }

        $media->unapprove();

        return response()->json([
            'message' => 'メディアの承認を取り消しました',
            'data' => $media,
        ]);
    }

    /**
     * 承認待ちメディア一覧（管理者のみ）
     */
    public function pending(): JsonResponse
    {
        // 簡易的な管理者チェック（実際は適切な権限管理を実装）
        $user = Auth::user();
        if (! $user || $user->email !== 'admin@example.com') {
            return response()->json(['error' => '管理者権限が必要です'], 403);
        }

        $media = RecipeMedia::pending()
            ->with(['recipe:id,name', 'user:id,name'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json([
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    /**
     * メディアタイプ検出
     */
    private function detectMediaType($file): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        throw new \InvalidArgumentException('サポートされていないファイル形式です');
    }

    /**
     * ユニークなファイル名生成
     */
    private function generateUniqueFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * メタデータ抽出
     */
    private function extractMetadata($file, string $mediaType): array
    {
        $metadata = [];

        try {
            if ($mediaType === 'image') {
                // 基本的な画像情報のみ取得（Interventionライブラリが使用できない場合）
                $imageInfo = getimagesize($file->path());
                if ($imageInfo) {
                    $metadata = [
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1],
                        'orientation' => $imageInfo[0] > $imageInfo[1] ? 'landscape' : 'portrait',
                    ];
                }
            } elseif ($mediaType === 'video') {
                // 動画のメタデータ取得（ffmpegが必要）
                $metadata = [
                    'duration' => null, // 実装時にffmpegで取得
                    'resolution' => null,
                ];
            }
        } catch (\Exception $e) {
            // メタデータ取得に失敗しても継続
            $metadata = ['error' => 'メタデータの取得に失敗しました'];
        }

        return $metadata;
    }

    /**
     * 画像最適化
     */
    private function optimizeImage(string $path): void
    {
        // 基本的な最適化（Interventionライブラリが使用できない場合はスキップ）
        // 実際の本番環境では適切な画像最適化ライブラリを使用
    }
}
