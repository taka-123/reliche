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
