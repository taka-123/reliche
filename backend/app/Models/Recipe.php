<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cooking_time',
        'instructions',
        'servings',
        'calories',
        'tags',
        'category',
        'source',
    ];

    protected $casts = [
        'instructions' => 'array',
        'tags' => 'array',
    ];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(RecipeReview::class);
    }

    public function media()
    {
        return $this->hasMany(RecipeMedia::class);
    }

    public function approvedMedia()
    {
        return $this->hasMany(RecipeMedia::class)->approved();
    }

    public function images()
    {
        return $this->hasMany(RecipeMedia::class)->images()->approved();
    }

    public function videos()
    {
        return $this->hasMany(RecipeMedia::class)->videos()->approved();
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('source', 'ai_generated');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByTags($query, array $tags)
    {
        return $query->whereJsonContains('tags', $tags);
    }

    public function isAiGenerated()
    {
        return $this->source === 'ai_generated';
    }

    public function getCaloriesPerServingAttribute()
    {
        return $this->calories ? round($this->calories / max($this->servings, 1)) : null;
    }

    // 評価関連アクセサ（N+1クエリ対策でキャッシュ対応）
    public function getAverageRatingAttribute()
    {
        // 既にwithCountやwithAvgで取得済みの場合はそれを使用
        if (isset($this->attributes['reviews_avg_rating'])) {
            return round((float) $this->getAttributeValue('reviews_avg_rating'), 1);
        }

        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function getReviewCountAttribute()
    {
        // 既にwithCountで取得済みの場合はそれを使用
        if (isset($this->attributes['reviews_count'])) {
            return (int) $this->getAttributeValue('reviews_count');
        }

        return $this->reviews()->count();
    }

    public function getAverageTasteScoreAttribute()
    {
        if (isset($this->attributes['reviews_avg_taste_score'])) {
            return round((float) $this->getAttributeValue('reviews_avg_taste_score'), 1);
        }

        return round($this->reviews()->whereNotNull('taste_score')->avg('taste_score') ?? 0, 1);
    }

    public function getAverageDifficultyScoreAttribute()
    {
        if (isset($this->attributes['reviews_avg_difficulty_score'])) {
            return round((float) $this->getAttributeValue('reviews_avg_difficulty_score'), 1);
        }

        return round($this->reviews()->whereNotNull('difficulty_score')->avg('difficulty_score') ?? 0, 1);
    }

    public function getAverageInstructionClarityAttribute()
    {
        if (isset($this->attributes['reviews_avg_instruction_clarity'])) {
            return round((float) $this->getAttributeValue('reviews_avg_instruction_clarity'), 1);
        }

        return round($this->reviews()->whereNotNull('instruction_clarity')->avg('instruction_clarity') ?? 0, 1);
    }

    // 評価関連スコープ
    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->whereHas('reviews', function ($q) use ($minRating) {
            $q->havingRaw('AVG(rating) >= ?', [$minRating]);
        });
    }

    public function scopeLowRated($query, $maxRating = 3.0)
    {
        return $query->whereHas('reviews', function ($q) use ($maxRating) {
            $q->havingRaw('AVG(rating) <= ?', [$maxRating]);
        });
    }

    // 品質管理用メソッド
    public function isLowQuality($threshold = 3.0, $minReviews = 3)
    {
        return $this->review_count >= $minReviews && $this->average_rating <= $threshold;
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
