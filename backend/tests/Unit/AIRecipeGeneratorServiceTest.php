<?php

use App\Models\Recipe;
use App\Services\AIRecipeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.gemini.api_key' => 'test-api-key',
        'services.gemini.model' => 'gemini-2.5-flash-lite',
        'services.gemini.base_url' => 'https://test-api.example.com',
        'ai.recipe.max_retries' => 1,
        'ai.recipe.timeout' => 5,
        'ai.recipe.cache_ttl' => 10,
    ]);
});

test('validates recipe data correctly', function () {
    $service = new AIRecipeGeneratorService;

    $validData = [
        'title' => 'テストレシピ',
        'cooking_time' => 30,
        'servings' => 4,
        'calories' => 500,
        'tags' => ['簡単', 'ヘルシー'],
        'category' => '和食',
        'ingredients' => [
            ['name' => '鶏肉', 'amount' => '300g'],
            ['name' => '玉ねぎ', 'amount' => '1個'],
        ],
        'instructions' => [
            '鶏肉を切る',
            '玉ねぎを炒める',
            '調味料を加える',
        ],
    ];

    $result = $service->saveRecipe($validData);

    expect($result)->toBeInstanceOf(Recipe::class);
    expect($result->name)->toBe('テストレシピ');
    expect($result->source)->toBe('ai_generated');
});

test('throws exception for invalid cooking time', function () {
    $service = new AIRecipeGeneratorService;

    $invalidData = [
        'title' => 'テストレシピ',
        'cooking_time' => 200, // 無効な調理時間
        'servings' => 4,
        'calories' => 500,
        'tags' => ['簡単'],
        'category' => '和食',
        'ingredients' => [
            ['name' => '鶏肉', 'amount' => '300g'],
            ['name' => '玉ねぎ', 'amount' => '1個'],
        ],
        'instructions' => [
            '鶏肉を切る',
            '玉ねぎを炒める',
            '調味料を加える',
        ],
    ];

    $service->saveRecipe($invalidData);
})->throws(Exception::class, 'Invalid cooking_time');

test('throws exception for invalid servings', function () {
    $service = new AIRecipeGeneratorService;

    $invalidData = [
        'title' => 'テストレシピ',
        'cooking_time' => 30,
        'servings' => 10, // 無効な人数
        'calories' => 500,
        'tags' => ['簡単'],
        'category' => '和食',
        'ingredients' => [
            ['name' => '鶏肉', 'amount' => '300g'],
            ['name' => '玉ねぎ', 'amount' => '1個'],
        ],
        'instructions' => [
            '鶏肉を切る',
            '玉ねぎを炒める',
            '調味料を加える',
        ],
    ];

    $service->saveRecipe($invalidData);
})->throws(Exception::class, 'Invalid servings');

test('throws exception for missing required fields', function () {
    $service = new AIRecipeGeneratorService;

    $invalidData = [
        'title' => 'テストレシピ',
        'cooking_time' => 30,
        // servings が欠如
        'calories' => 500,
        'tags' => ['簡単'],
        'category' => '和食',
        'ingredients' => [
            ['name' => '鶏肉', 'amount' => '300g'],
        ],
        'instructions' => [
            '鶏肉を切る',
        ],
    ];

    $service->saveRecipe($invalidData);
})->throws(Exception::class, 'Missing required field');

test('creates ingredients and attaches to recipe', function () {
    $service = new AIRecipeGeneratorService;

    $recipeData = [
        'title' => 'テストレシピ',
        'cooking_time' => 30,
        'servings' => 4,
        'calories' => 500,
        'tags' => ['簡単'],
        'category' => '和食',
        'ingredients' => [
            ['name' => '新しい食材', 'amount' => '300g'],
            ['name' => '別の食材', 'amount' => '1個'],
        ],
        'instructions' => [
            '手順1',
            '手順2',
            '手順3',
        ],
    ];

    $recipe = $service->saveRecipe($recipeData);

    expect($recipe->ingredients)->toHaveCount(2);
    expect($recipe->ingredients->first()->name)->toBe('新しい食材');
    expect($recipe->ingredients->first()->pivot->quantity)->toBe('300g');
});

test('parses JSON response correctly', function () {
    $service = new AIRecipeGeneratorService;

    // プライベートメソッドをテストするためのリフレクション
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseRecipeResponse');
    $method->setAccessible(true);

    $responseText = '{"title":"テストレシピ","cooking_time":30,"servings":4,"calories":500,"tags":["簡単"],"category":"和食","ingredients":[{"name":"鶏肉","amount":"300g"}],"instructions":["手順1","手順2","手順3"]}';

    $result = $method->invoke($service, $responseText);

    expect($result)->toBeArray();
    expect($result['title'])->toBe('テストレシピ');
    expect($result['cooking_time'])->toBe(30);
});

test('allows creation in test environment without API key', function () {
    config(['services.gemini.api_key' => null]);

    // テスト環境では API キーなしでもサービス作成可能
    $service = new AIRecipeGeneratorService;
    
    expect($service)->toBeInstanceOf(AIRecipeGeneratorService::class);
});
