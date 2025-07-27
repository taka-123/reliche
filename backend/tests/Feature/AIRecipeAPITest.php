<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIRecipeAPITest extends TestCase
{
    use RefreshDatabase;

    /**
     * 基本レシピ生成APIエンドポイントのテスト
     */
    public function test_basic_recipe_generation_endpoint_works(): void
    {
        // テスト環境ではGemini APIをモック
        $this->mock(\App\Services\AIRecipeGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateBasicRecipe')
                ->once()
                ->with(null)
                ->andReturn([
                    'recipe' => [
                        'title' => 'テスト和食レシピ',
                        'cooking_time' => 30,
                        'servings' => 2,
                        'calories' => 400,
                        'tags' => ['和食', '簡単'],
                        'category' => '和食',
                        'instructions' => ['手順1', '手順2', '手順3'],
                    ],
                    'recipe_ingredients' => [
                        [
                            'name' => 'テスト食材',
                            'amount' => '100g',
                            'nutrition_notes' => 'テスト栄養メモ',
                            'cooking_method_tips' => 'テスト調理コツ',
                        ],
                    ],
                ]);
        });

        $response = $this->postJson('/api/ai-recipes/generate', [
            'category' => null,
            'save_to_db' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'recipe' => [
                    'title',
                    'cooking_time',
                    'servings',
                    'calories',
                    'tags',
                    'category',
                    'instructions',
                ],
                'recipe_ingredients' => [
                    '*' => [
                        'name',
                        'amount',
                        'nutrition_notes',
                        'cooking_method_tips',
                    ],
                ],
            ],
            'message',
        ]);
        $response->assertJson([
            'success' => true,
            'message' => 'レシピを生成しました',
        ]);
    }

    /**
     * 食材指定レシピ生成APIエンドポイントのテスト
     */
    public function test_ingredients_recipe_generation_endpoint_works(): void
    {
        $this->mock(\App\Services\AIRecipeGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateRecipeByIngredients')
                ->once()
                ->with(['豚肉', 'キャベツ'])
                ->andReturn([
                    'recipe' => [
                        'title' => '豚キャベツ炒め',
                        'cooking_time' => 15,
                        'servings' => 2,
                        'calories' => 350,
                        'tags' => ['時短', '簡単'],
                        'category' => '中華',
                        'instructions' => ['手順1', '手順2', '手順3'],
                    ],
                    'recipe_ingredients' => [
                        [
                            'name' => '豚肉',
                            'amount' => '200g',
                            'nutrition_notes' => '',
                            'cooking_method_tips' => '',
                        ],
                        [
                            'name' => 'キャベツ',
                            'amount' => '1/4玉',
                            'nutrition_notes' => '',
                            'cooking_method_tips' => '',
                        ],
                    ],
                ]);
        });

        $response = $this->postJson('/api/ai-recipes/generate/ingredients', [
            'ingredients' => ['豚肉', 'キャベツ'],
            'save_to_db' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'message',
        ]);
    }

    /**
     * 制約条件付きレシピ生成APIエンドポイントのテスト
     */
    public function test_constraints_recipe_generation_endpoint_works(): void
    {
        $this->mock(\App\Services\AIRecipeGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateRecipeWithConstraints')
                ->once()
                ->with(['max_time' => 30, 'tags' => ['時短']])
                ->andReturn([
                    'recipe' => [
                        'title' => '時短レシピ',
                        'cooking_time' => 20,
                        'servings' => 2,
                        'calories' => 300,
                        'tags' => ['時短', '簡単'],
                        'category' => '洋食',
                        'instructions' => ['手順1', '手順2', '手順3'],
                    ],
                    'recipe_ingredients' => [
                        [
                            'name' => 'テスト食材',
                            'amount' => '100g',
                            'nutrition_notes' => '',
                            'cooking_method_tips' => '',
                        ],
                    ],
                ]);
        });

        $response = $this->postJson('/api/ai-recipes/generate/constraints', [
            'max_time' => 30,
            'tags' => ['時短'],
            'save_to_db' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * バリデーションエラーのテスト
     */
    public function test_ingredients_validation_error(): void
    {
        $response = $this->postJson('/api/ai-recipes/generate/ingredients', [
            'ingredients' => [], // 空配列はエラー
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
    }

    /**
     * 制約条件のバリデーションエラーのテスト
     */
    public function test_constraints_validation_error(): void
    {
        $response = $this->postJson('/api/ai-recipes/generate/constraints', [
            'max_time' => 200, // 120分を超える値はエラー
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
    }

    /**
     * データベース保存機能のテスト
     */
    public function test_recipe_saving_to_database(): void
    {
        $this->mock(\App\Services\AIRecipeGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateBasicRecipe')
                ->once()
                ->andReturn([
                    'recipe' => [
                        'title' => 'DB保存テストレシピ',
                        'cooking_time' => 25,
                        'servings' => 2,
                        'calories' => 400,
                        'tags' => ['テスト'],
                        'category' => '和食',
                        'instructions' => ['手順1', '手順2', '手順3'],
                    ],
                    'recipe_ingredients' => [
                        [
                            'name' => 'テスト食材',
                            'amount' => '100g',
                            'nutrition_notes' => '',
                            'cooking_method_tips' => '',
                        ],
                    ],
                ]);

            $mock->shouldReceive('saveRecipe')
                ->once()
                ->andReturn(new \App\Models\Recipe(['id' => 1]));
        });

        $response = $this->postJson('/api/ai-recipes/generate', [
            'category' => '和食',
            'save_to_db' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'message',
            'saved_recipe_id',
        ]);
    }

    /**
     * ルート競合確認テスト：既存のGETルートとの競合がないことを確認
     */
    public function test_no_route_conflicts_with_existing_routes(): void
    {
        // 既存のGET /api/recipes ルートが正常に動作することを確認
        $response = $this->getJson('/api/recipes');
        $response->assertStatus(200);

        // AI生成ルートが正常に動作することを確認（モック付き）
        $this->mock(\App\Services\AIRecipeGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generateBasicRecipe')->andReturn([
                'recipe' => [
                    'title' => 'テストレシピ',
                    'cooking_time' => 30,
                    'servings' => 2,
                    'calories' => 400,
                    'tags' => ['テスト'],
                    'category' => '和食',
                    'instructions' => ['手順1'],
                ],
                'recipe_ingredients' => [],
            ]);
        });

        $response = $this->postJson('/api/ai-recipes/generate', [
            'save_to_db' => false,
        ]);
        $response->assertStatus(200);
    }

    /**
     * HTTPメソッドのテスト：GETメソッドでPOSTルートにアクセスするとエラーになることを確認
     */
    public function test_http_method_not_allowed(): void
    {
        $response = $this->getJson('/api/ai-recipes/generate');
        $response->assertStatus(405);
    }

    /**
     * 存在しないルートのテスト
     */
    public function test_non_existent_route(): void
    {
        $response = $this->postJson('/api/ai-recipes/non-existent');
        $response->assertStatus(404);
    }
}
