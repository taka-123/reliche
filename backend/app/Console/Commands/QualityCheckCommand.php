<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Models\RecipeReview;
use App\Services\AIRecipeGeneratorService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QualityCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipe:quality-check 
                            {--threshold=3.0 : Average rating threshold for low quality recipes}
                            {--min-reviews=3 : Minimum number of reviews required for evaluation}
                            {--days-old=30 : Consider recipes older than this many days}
                            {--generate-alternatives : Generate alternative recipes for low quality ones}
                            {--delete-low-quality : Delete low quality recipes after confirmation}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check recipe quality based on user reviews and optionally remove/replace low quality recipes';

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
        $threshold = (float) $this->option('threshold');
        $minReviews = (int) $this->option('min-reviews');
        $daysOld = (int) $this->option('days-old');
        $generateAlternatives = $this->option('generate-alternatives');
        $deleteLowQuality = $this->option('delete-low-quality');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 レシピ品質チェックを開始します...');
        $this->info("評価閾値: {$threshold}/5.0");
        $this->info("最小レビュー数: {$minReviews}件");
        $this->info("対象期間: {$daysOld}日以上前のレシピ");

        if ($dryRun) {
            $this->warn('💡 --dry-run モードのため、実際の変更は行われません');
        }

        $this->newLine();

        try {
            // 1. 低品質レシピの検出
            $lowQualityRecipes = $this->findLowQualityRecipes($threshold, $minReviews, $daysOld);

            if ($lowQualityRecipes->isEmpty()) {
                $this->info('✅ 品質の低いレシピは見つかりませんでした');

                // 品質レポートの生成（レシピがなくても全体統計は表示）
                $this->generateQualityReport();

                return Command::SUCCESS;
            }

            // 2. 結果の表示
            $this->displayLowQualityRecipes($lowQualityRecipes);

            // 3. 代替レシピ生成
            if ($generateAlternatives) {
                $this->generateAlternativeRecipes($lowQualityRecipes, $dryRun);
            }

            // 4. 低品質レシピの削除
            if ($deleteLowQuality) {
                $this->deleteLowQualityRecipes($lowQualityRecipes, $dryRun, $force);
            }

            // 5. 品質レポートの生成
            $this->generateQualityReport();

            $this->info('🎉 品質チェック完了!');

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ 品質チェック失敗: '.$e->getMessage());
            Log::error('Quality check failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return Command::FAILURE;
        }
    }

    /**
     * 低品質レシピを検出
     */
    private function findLowQualityRecipes(float $threshold, int $minReviews, int $daysOld)
    {
        $this->info('🔍 低品質レシピを検索中...');

        // まず、十分なレビューを持つレシピを取得
        $recipesWithEnoughReviews = Recipe::query()
            ->selectRaw('recipes.*, COUNT(recipe_reviews.id) as review_count, AVG(recipe_reviews.rating) as avg_rating')
            ->leftJoin('recipe_reviews', function ($join) {
                $join->on('recipes.id', '=', 'recipe_reviews.recipe_id')
                    ->whereNull('recipe_reviews.deleted_at');
            })
            ->where('recipes.created_at', '<=', now()->subDays($daysOld))
            ->whereNull('recipes.deleted_at')
            ->groupBy('recipes.id')
            ->havingRaw('COUNT(recipe_reviews.id) >= ?', [$minReviews])
            ->havingRaw('AVG(recipe_reviews.rating) <= ?', [$threshold])
            ->with(['reviews', 'ingredients'])
            ->get();

        $this->info("📊 {$recipesWithEnoughReviews->count()}件の低品質レシピを検出しました");

        return $recipesWithEnoughReviews;
    }

    /**
     * 低品質レシピの表示
     */
    private function displayLowQualityRecipes($recipes): void
    {
        if ($recipes->isEmpty()) {
            return;
        }

        $this->warn('⚠️  低品質レシピ一覧:');
        $this->newLine();

        $tableData = [];
        foreach ($recipes as $recipe) {
            $tableData[] = [
                $recipe->id,
                substr($recipe->name, 0, 30).(strlen($recipe->name) > 30 ? '...' : ''),
                number_format($recipe->average_rating, 1),
                $recipe->review_count,
                $recipe->category,
                $recipe->created_at->format('Y-m-d'),
            ];
        }

        $this->table([
            'ID', 'レシピ名', '平均評価', 'レビュー数', 'カテゴリ', '作成日',
        ], $tableData);
    }

    /**
     * 代替レシピの生成
     */
    private function generateAlternativeRecipes($lowQualityRecipes, bool $dryRun): void
    {
        $this->info('🤖 代替レシピを生成中...');

        $progressBar = $this->output->createProgressBar($lowQualityRecipes->count());
        $progressBar->start();

        $generated = 0;
        $failed = 0;

        foreach ($lowQualityRecipes as $recipe) {
            try {
                if ($dryRun) {
                    $this->line("🔮 代替レシピを生成予定: {$recipe->name} (カテゴリ: {$recipe->category})");
                } else {
                    // 同じカテゴリで代替レシピを生成
                    $alternativeData = $this->recipeService->generateBasicRecipe($recipe->category);
                    $alternativeRecipe = $this->recipeService->saveRecipe($alternativeData);

                    $this->line("✅ 代替レシピ生成: {$alternativeRecipe->name} (ID: {$alternativeRecipe->id})");
                    $generated++;
                }
            } catch (Exception $e) {
                $this->error("❌ 代替レシピ生成失敗: {$recipe->name} - {$e->getMessage()}");
                $failed++;
            }

            $progressBar->advance();
            usleep(1000000); // 1秒待機（API制限対策）
        }

        $progressBar->finish();
        $this->newLine();

        if (! $dryRun) {
            $this->info("📊 代替レシピ生成結果: 成功 {$generated}件, 失敗 {$failed}件");
        }
    }

    /**
     * 低品質レシピの削除
     */
    private function deleteLowQualityRecipes($lowQualityRecipes, bool $dryRun, bool $force): void
    {
        if (! $force && ! $dryRun) {
            $confirmed = $this->confirm(
                "🗑️  {$lowQualityRecipes->count()}件の低品質レシピを削除しますか？"
            );

            if (! $confirmed) {
                $this->info('削除をキャンセルしました');

                return;
            }
        }

        $this->info('🗑️  低品質レシピを削除中...');

        $deleted = 0;
        foreach ($lowQualityRecipes as $recipe) {
            if ($dryRun) {
                $this->line("🗑️  削除予定: {$recipe->name} (評価: {$recipe->average_rating})");
            } else {
                $recipe->delete();
                $this->line("✅ 削除完了: {$recipe->name}");
                $deleted++;
            }
        }

        if (! $dryRun) {
            $this->info("📊 削除完了: {$deleted}件のレシピを削除しました");
        }
    }

    /**
     * 品質レポートの生成
     */
    private function generateQualityReport(): void
    {
        $this->info('📊 品質レポートを生成中...');

        $totalRecipes = Recipe::count();
        $recipesWithReviews = Recipe::whereHas('reviews')->count();
        $averageRating = RecipeReview::avg('rating') ?? 0;
        $totalReviews = RecipeReview::count();

        // 評価分布の計算
        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = RecipeReview::where('rating', $i)->count();
            $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100, 1) : 0;
            $ratingDistribution[] = [
                "★{$i}",
                $count,
                "{$percentage}%",
            ];
        }

        $this->newLine();
        $this->info('📈 全体品質レポート:');
        $this->table(['項目', '値'], [
            ['総レシピ数', number_format($totalRecipes)],
            ['レビュー付きレシピ数', number_format($recipesWithReviews)],
            ['総レビュー数', number_format($totalReviews)],
            ['全体平均評価', number_format($averageRating, 2).'/5.0'],
            ['レビュー率', $totalRecipes > 0 ? round(($recipesWithReviews / $totalRecipes) * 100, 1).'%' : '0%'],
        ]);

        $this->newLine();
        $this->info('⭐ 評価分布:');
        $this->table(['評価', '件数', '割合'], $ratingDistribution);
    }
}
