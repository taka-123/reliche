<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Recipe Generation Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for AI-powered recipe generation
    | including retry policies, timeouts, and caching settings.
    |
    */

    'recipe' => [
        'max_retries' => env('AI_RECIPE_MAX_RETRIES', 3),
        'timeout' => env('AI_RECIPE_TIMEOUT', 30),
        'cache_ttl' => env('AI_RECIPE_CACHE_TTL', 3600),

        'categories' => [
            '和食' => 'Japanese cuisine',
            '洋食' => 'Western cuisine',
            '中華' => 'Chinese cuisine',
            'イタリアン' => 'Italian cuisine',
            'フレンチ' => 'French cuisine',
            'その他' => 'Other',
        ],

        'tags' => [
            '時短' => 'Quick cooking',
            '節約' => 'Budget-friendly',
            'ヘルシー' => 'Healthy',
            '簡単' => 'Easy',
            'ボリューム' => 'Hearty',
            'おつまみ' => 'Appetizer',
            'デザート' => 'Dessert',
        ],

        'validation' => [
            'min_cooking_time' => 5,
            'max_cooking_time' => 120,
            'min_servings' => 1,
            'max_servings' => 6,
            'min_calories' => 50,
            'max_calories' => 1500,
            'min_ingredients' => 2,
            'max_ingredients' => 15,
            'min_instructions' => 3,
            'max_instructions' => 15,
        ],
    ],

];
