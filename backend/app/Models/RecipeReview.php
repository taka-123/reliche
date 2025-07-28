<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'recipe_id',
        'user_id',
        'rating',
        'taste_score',
        'difficulty_score',
        'instruction_clarity',
        'comment',
        'review_images',
    ];

    protected $casts = [
        'review_images' => 'array',
        'rating' => 'integer',
        'taste_score' => 'integer',
        'difficulty_score' => 'integer',
        'instruction_clarity' => 'integer',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 監査ログ関係のリレーション
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // スコープ: 評価による並び替え
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRated($query, $maxRating = 3)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    // アクセサ: 総合スコア計算
    public function getAverageScoreAttribute()
    {
        $scores = array_filter([
            $this->taste_score,
            $this->difficulty_score,
            $this->instruction_clarity,
        ]);

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : $this->rating;
    }

    // バリデーション関連
    public function isValidRating($value)
    {
        return is_int($value) && $value >= 1 && $value <= 5;
    }

    // 自動監査ロジック
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save(); // deleted_byを保存してからソフトデリート
            }
        });
    }
}
