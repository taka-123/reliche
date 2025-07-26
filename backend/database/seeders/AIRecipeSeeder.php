<?php

namespace Database\Seeders;

use App\Services\AIRecipeGeneratorService;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AIRecipeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🍳 AIレシピシーダーを開始します...');

        // Gemini APIキーが設定されているかチェック
        if (empty(config('services.gemini.api_key'))) {
            $this->command->warn('⚠️  GEMINI_API_KEY が設定されていないため、AIレシピの生成をスキップします。');
            $this->command->warn('   APIキーを設定してからシーダーを実行してください。');

            return;
        }

        $recipeService = app(AIRecipeGeneratorService::class);

        $categories = [
            '和食' => 12,
            '洋食' => 12,
            '中華' => 10,
            'イタリアン' => 8,
            'その他' => 8,
        ];

        $totalRecipes = array_sum($categories);
        $this->command->info("合計 {$totalRecipes} 件のレシピを生成します");

        $progressBar = $this->command->getOutput()->createProgressBar($totalRecipes);
        $progressBar->start();

        $generated = 0;
        $failed = 0;

        foreach ($categories as $category => $count) {
            $this->command->newLine();
            $this->command->info("📂 {$category} カテゴリのレシピを {$count} 件生成中...");

            for ($i = 0; $i < $count; $i++) {
                try {
                    $recipeData = $recipeService->generateBasicRecipe($category);
                    $recipe = $recipeService->saveRecipe($recipeData);

                    $generated++;
                    Log::info('AI Recipe generated', [
                        'id' => $recipe->id,
                        'name' => $recipe->name,
                        'category' => $category,
                    ]);

                } catch (Exception $e) {
                    $failed++;
                    $this->command->error('❌ レシピ生成失敗: '.$e->getMessage());
                    Log::error('AI Recipe generation failed', [
                        'category' => $category,
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();

                // API制限を考慮して待機（500ms）
                usleep(500000);
            }
        }

        $progressBar->finish();
        $this->command->newLine(2);

        // 結果サマリー
        $this->command->info('🎉 AIレシピシーダー完了!');
        $this->command->table([
            '項目', '件数',
        ], [
            ['成功', $generated],
            ['失敗', $failed],
            ['合計', $totalRecipes],
        ]);

        if ($generated > 0) {
            $this->command->info("✅ {$generated} 件のAI生成レシピがデータベースに保存されました。");
        }

        if ($failed > 0) {
            $this->command->warn("⚠️  {$failed} 件のレシピ生成に失敗しました。ログを確認してください。");
        }
    }
}
