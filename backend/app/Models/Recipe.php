<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
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
}
