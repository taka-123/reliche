<?php

namespace App\Console\Commands;

use App\Services\AIRecipeGeneratorService;
use Exception;
use Illuminate\Console\Command;

class GenerateRecipesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipe:generate 
                            {--count=1 : Number of recipes to generate}
                            {--category= : Recipe category (和食, 洋食, 中華, イタリアン)}
                            {--ingredients= : Comma-separated list of ingredients}
                            {--tags= : Comma-separated list of tags}
                            {--max-time= : Maximum cooking time in minutes}
                            {--dry-run : Show generated recipes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI-powered recipes using Gemini API';

    private AIRecipeGeneratorService $recipeService;

    public function __construct(AIRecipeGeneratorService $recipeService)
    {
        parent::__construct();
        $this->recipeService = $recipeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = max(1, (int) $this->option('count'));
        $category = $this->option('category');
        $ingredients = $this->parseIngredients($this->option('ingredients'));
        $tags = $this->parseTags($this->option('tags'));
        $maxTime = $this->option('max-time');
        $dryRun = $this->option('dry-run');

        $this->info('🍳 AIレシピ生成を開始します...');
        $this->info("生成数: {$count}");

        if ($category) {
            $this->info("カテゴリ: {$category}");
        }
        if ($ingredients) {
            $this->info('指定食材: '.implode(', ', $ingredients));
        }
        if ($tags) {
            $this->info('タグ: '.implode(', ', $tags));
        }
        if ($maxTime) {
            $this->info("最大調理時間: {$maxTime}分");
        }

        $this->newLine();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $generated = 0;
        $failed = 0;

        for ($i = 0; $i < $count; $i++) {
            try {
                $recipeData = $this->generateSingleRecipe($category, $ingredients, $tags, $maxTime);

                if ($dryRun) {
                    $this->displayRecipe($recipeData, $i + 1);
                } else {
                    $recipe = $this->recipeService->saveRecipe($recipeData);
                    $this->line("✅ レシピ「{$recipe->name}」を保存しました (ID: {$recipe->id})");
                }

                $generated++;

            } catch (Exception $e) {
                $failed++;
                $this->error('❌ レシピ生成失敗: '.$e->getMessage());
            }

            $progressBar->advance();

            // API制限を考慮して少し待機
            if ($i < $count - 1) {
                usleep(500000); // 0.5秒待機
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // 結果サマリー
        $this->info('🎉 レシピ生成完了!');
        $this->table([
            '項目', '件数',
        ], [
            ['成功', $generated],
            ['失敗', $failed],
            ['合計', $count],
        ]);

        if ($dryRun) {
            $this->warn('💡 --dry-run モードのため、レシピは保存されていません');
        }

        return $generated > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * 単一レシピを生成
     */
    private function generateSingleRecipe(?string $category, array $ingredients, array $tags, ?string $maxTime): array
    {
        if (! empty($ingredients)) {
            return $this->recipeService->generateRecipeByIngredients($ingredients);
        }

        if (! empty($tags) || $maxTime) {
            $constraints = [];
            if ($tags) {
                $constraints['tags'] = $tags;
            }
            if ($maxTime) {
                $constraints['max_time'] = (int) $maxTime;
            }

            return $this->recipeService->generateRecipeWithConstraints($constraints);
        }

        return $this->recipeService->generateBasicRecipe($category);
    }

    /**
     * 食材文字列を配列に変換
     */
    private function parseIngredients(?string $ingredients): array
    {
        if (! $ingredients) {
            return [];
        }

        return array_map('trim', explode(',', $ingredients));
    }

    /**
     * タグ文字列を配列に変換
     */
    private function parseTags(?string $tags): array
    {
        if (! $tags) {
            return [];
        }

        return array_map('trim', explode(',', $tags));
    }

    /**
     * レシピを表示
     */
    private function displayRecipe(array $recipeData, int $index): void
    {
        $this->newLine();
        $this->info("📋 レシピ #{$index}: {$recipeData['title']}");

        $this->table(['項目', '値'], [
            ['調理時間', $recipeData['cooking_time'].'分'],
            ['人数', $recipeData['servings'].'人分'],
            ['カロリー', $recipeData['calories'].'kcal'],
            ['カテゴリ', $recipeData['category']],
            ['タグ', implode(', ', $recipeData['tags'])],
        ]);

        $this->info('🥗 材料:');
        foreach ($recipeData['ingredients'] as $ingredient) {
            $this->line("  • {$ingredient['name']}: {$ingredient['amount']}");
        }

        $this->info('👩‍🍳 作り方:');
        foreach ($recipeData['instructions'] as $index => $instruction) {
            $this->line('  '.($index + 1).". {$instruction}");
        }

        $this->newLine();
    }
}
