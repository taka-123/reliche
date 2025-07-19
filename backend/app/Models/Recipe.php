<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'name',
        'cooking_time',
        'instructions',
    ];

    protected $casts = [
        'instructions' => 'array',
    ];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    /**
     * このレシピをお気に入りにしているユーザー
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * お気に入り数を取得
     */
    public function favoritesCount()
    {
        return $this->favoritedBy()->count();
    }
}
