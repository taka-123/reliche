<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'recipe_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'recipe_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 特定のユーザーとレシピの組み合わせが既にお気に入りに登録されているかチェック
     */
    public static function isFavorited(int $userId, int $recipeId): bool
    {
        return static::where('user_id', $userId)
            ->where('recipe_id', $recipeId)
            ->exists();
    }

    /**
     * ユーザーのお気に入りレシピIDリストを取得
     */
    public static function getFavoriteRecipeIds(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('recipe_id')
            ->toArray();
    }
}
