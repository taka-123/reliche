<?php

use App\Services\AIRecipeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('services.gemini.api_key', 'test-api-key');
});

test('command runs with dry-run option', function () {
    $mockService = Mockery::mock(AIRecipeGeneratorService::class);
    $mockService->shouldReceive('generateBasicRecipe')
        ->once()
        ->andReturn([
            'title' => 'テストレシピ',
            'cooking_time' => 30,
            'servings' => 4,
            'calories' => 500,
            'tags' => ['簡単'],
            'category' => '和食',
            'ingredients' => [
                ['name' => '鶏肉', 'amount' => '300g'],
            ],
            'instructions' => [
                '手順1',
                '手順2',
                '手順3',
            ],
        ]);

    $this->app->instance(AIRecipeGeneratorService::class, $mockService);

    $this->artisan('recipe:generate --count=1 --dry-run')
        ->expectsOutput('🍳 AIレシピ生成を開始します...')
        ->expectsOutput('💡 --dry-run モードのため、レシピは保存されていません')
        ->assertExitCode(0);
});

test('command handles API errors gracefully', function () {
    $mockService = Mockery::mock(AIRecipeGeneratorService::class);
    $mockService->shouldReceive('generateBasicRecipe')
        ->once()
        ->andThrow(new Exception('API Error'));

    $this->app->instance(AIRecipeGeneratorService::class, $mockService);

    $this->artisan('recipe:generate --count=1 --dry-run')
        ->expectsOutput('❌ レシピ生成失敗: API Error')
        ->assertExitCode(1);
});

test('command parses ingredients option correctly', function () {
    $mockService = Mockery::mock(AIRecipeGeneratorService::class);
    $mockService->shouldReceive('generateRecipeByIngredients')
        ->once()
        ->with(['鶏肉', 'キャベツ', '玉ねぎ'])
        ->andReturn([
            'title' => 'テストレシピ',
            'cooking_time' => 30,
            'servings' => 4,
            'calories' => 500,
            'tags' => ['簡単'],
            'category' => '和食',
            'ingredients' => [
                ['name' => '鶏肉', 'amount' => '300g'],
            ],
            'instructions' => [
                '手順1',
                '手順2',
                '手順3',
            ],
        ]);

    $this->app->instance(AIRecipeGeneratorService::class, $mockService);

    $this->artisan('recipe:generate --ingredients="鶏肉,キャベツ,玉ねぎ" --count=1 --dry-run')
        ->expectsOutput('指定食材: 鶏肉, キャベツ, 玉ねぎ')
        ->assertExitCode(0);
});

test('command parses tags option correctly', function () {
    $mockService = Mockery::mock(AIRecipeGeneratorService::class);
    $mockService->shouldReceive('generateRecipeWithConstraints')
        ->once()
        ->with(['tags' => ['時短', '節約']])
        ->andReturn([
            'title' => 'テストレシピ',
            'cooking_time' => 30,
            'servings' => 4,
            'calories' => 500,
            'tags' => ['時短', '節約'],
            'category' => '和食',
            'ingredients' => [
                ['name' => '鶏肉', 'amount' => '300g'],
            ],
            'instructions' => [
                '手順1',
                '手順2',
                '手順3',
            ],
        ]);

    $this->app->instance(AIRecipeGeneratorService::class, $mockService);

    $this->artisan('recipe:generate --tags="時短,節約" --count=1 --dry-run')
        ->expectsOutput('タグ: 時短, 節約')
        ->assertExitCode(0);
});

test('command applies rate limiting between requests', function () {
    $mockService = Mockery::mock(AIRecipeGeneratorService::class);
    $mockService->shouldReceive('generateBasicRecipe')
        ->twice()
        ->andReturn([
            'title' => 'テストレシピ',
            'cooking_time' => 30,
            'servings' => 4,
            'calories' => 500,
            'tags' => ['簡単'],
            'category' => '和食',
            'ingredients' => [
                ['name' => '鶏肉', 'amount' => '300g'],
            ],
            'instructions' => [
                '手順1',
                '手順2',
                '手順3',
            ],
        ]);

    $this->app->instance(AIRecipeGeneratorService::class, $mockService);

    $startTime = microtime(true);

    $this->artisan('recipe:generate --count=2 --dry-run')
        ->assertExitCode(0);

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // 最低0.5秒の待機時間があることを確認
    expect($duration)->toBeGreaterThan(0.4);
});
