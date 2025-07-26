<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Recipe;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIRecipeGeneratorService
{
    private ?string $apiKey;

    private string $model;

    private string $baseUrl;

    private int $maxRetries;

    private int $timeout;

    private int $cacheTtl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash-lite');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->maxRetries = config('ai.recipe.max_retries', 3);
        $this->timeout = config('ai.recipe.timeout', 30);
        $this->cacheTtl = config('ai.recipe.cache_ttl', 3600);

        // コマンド実行時はAPIキーチェックをスキップ
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }
    }

    /**
     * 基本レシピを生成
     */
    public function generateBasicRecipe(?string $category = null): array
    {
        $cacheKey = 'ai_recipe_basic_'.($category ?? 'general').'_'.time();

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category) {
            $prompt = $this->buildBasicPrompt($category);

            return $this->callGeminiAPI($prompt);
        });
    }

    /**
     * 食材指定レシピを生成
     */
    public function generateRecipeByIngredients(array $ingredients): array
    {
        $cacheKey = 'ai_recipe_ingredients_'.md5(implode(',', $ingredients));

        return Cache::remember($cacheKey, $this->cacheTtl / 6, function () use ($ingredients) {
            $prompt = $this->buildIngredientsPrompt($ingredients);

            return $this->callGeminiAPI($prompt);
        });
    }

    /**
     * 制約条件付きレシピを生成
     */
    public function generateRecipeWithConstraints(array $constraints): array
    {
        $cacheKey = 'ai_recipe_constraints_'.md5(json_encode($constraints));

        return Cache::remember($cacheKey, $this->cacheTtl / 2, function () use ($constraints) {
            $prompt = $this->buildConstraintsPrompt($constraints);

            return $this->callGeminiAPI($prompt);
        });
    }

    /**
     * レシピをデータベースに保存
     */
    public function saveRecipe(array $recipeData): Recipe
    {
        $validatedData = $this->validateRecipeData($recipeData);

        $recipe = Recipe::create([
            'name' => $validatedData['title'],
            'cooking_time' => $validatedData['cooking_time'],
            'servings' => $validatedData['servings'],
            'calories' => $validatedData['calories'],
            'tags' => $validatedData['tags'],
            'category' => $validatedData['category'],
            'instructions' => $validatedData['instructions'],
            'source' => 'ai_generated',
        ]);

        $this->attachIngredients($recipe, $validatedData['ingredients']);

        return $recipe;
    }

    /**
     * 基本プロンプトを構築
     */
    private function buildBasicPrompt(?string $category = null): string
    {
        $basePrompt = $this->getBasePrompt();

        if ($category) {
            $categoryPrompt = $this->getCategoryPrompt($category);

            return $basePrompt."\n\n".$categoryPrompt;
        }

        return $basePrompt;
    }

    /**
     * 食材指定プロンプトを構築
     */
    private function buildIngredientsPrompt(array $ingredients): string
    {
        $basePrompt = $this->getBasePrompt();
        $ingredientsList = implode(', ', $ingredients);

        return $basePrompt."\n\n指定食材: {$ingredientsList}\n上記の食材を必ず使用してレシピを作成してください。足りない食材は一般的な家庭にある調味料や基本食材で補完してください。";
    }

    /**
     * 制約条件プロンプトを構築
     */
    private function buildConstraintsPrompt(array $constraints): string
    {
        $basePrompt = $this->getBasePrompt();
        $constraintsText = [];

        if (isset($constraints['max_time'])) {
            $constraintsText[] = "調理時間: {$constraints['max_time']}分以内";
        }
        if (isset($constraints['tags'])) {
            $tags = implode(', ', $constraints['tags']);
            $constraintsText[] = "タグ: {$tags}";
        }
        if (isset($constraints['difficulty'])) {
            $constraintsText[] = "難易度: {$constraints['difficulty']}";
        }

        $constraintsStr = implode("\n- ", $constraintsText);

        return $basePrompt."\n\n制約条件:\n- {$constraintsStr}\n\n上記条件を満たすレシピを作成してください。";
    }

    /**
     * 基本プロンプトテンプレート
     */
    private function getBasePrompt(): string
    {
        return '日本の家庭料理のレシピを1つ作成してください。実際に作って美味しく、失敗しにくいレシピを心がけてください。

以下のJSON形式で正確に出力してください：

{
  "title": "レシピ名",
  "cooking_time": 調理時間（分、数値のみ）,
  "servings": 人数（数値のみ）,
  "calories": カロリー（1人前あたりのkcal、数値のみ）,
  "tags": ["時短", "節約", "簡単", "ヘルシー", "主菜", "副菜", "和風", "洋風", "中華風"等から3-5個選択],
  "category": "和食または洋食または中華またはイタリアン",
  "ingredients": [
    {"name": "食材名", "amount": "具体的な分量"}
  ],
  "instructions": [
    "手順1",
    "手順2"
  ]
}

制約条件：
- 調理時間は5〜60分
- 人数は1〜6人
- カロリーは100〜800kcal/人
- 材料は2〜15個
- 手順は3〜10個
- 分量は具体的に記載（「適量」「お好みで」禁止）
- 実際に作れる現実的なレシピ
- 日本で入手可能な食材のみ
- 一般的な家庭用調理器具のみ使用
- 火加減・時間・温度は具体的に指定
- JSON形式以外は一切出力しない';
    }

    /**
     * カテゴリ別プロンプトを取得
     */
    private function getCategoryPrompt(string $category): string
    {
        $categoryPrompts = [
            '和食' => 'カテゴリ: 和食\n使用する調味料: 醤油、味噌、みりん、酒、だし等の和風調味料を中心に',
            '洋食' => 'カテゴリ: 洋食\n使用する調味料: 塩、こしょう、オリーブオイル、バター、チーズ等の洋風調味料を中心に',
            '中華' => 'カテゴリ: 中華\n使用する調味料: 醤油、オイスターソース、ごま油、豆板醤等の中華調味料を中心に',
            'イタリアン' => 'カテゴリ: イタリアン\n使用する調味料: オリーブオイル、トマト、バジル、パルメザンチーズ等を中心に',
        ];

        return $categoryPrompts[$category] ?? '';
    }

    /**
     * Gemini APIを呼び出し
     */
    private function callGeminiAPI(string $prompt): array
    {
        // 実際のAPI呼び出し時にAPIキーをチェック
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.9,
                'maxOutputTokens' => 1000,
            ],
        ];

        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])
                    ->timeout($this->timeout)
                    ->post($url.'?key='.$this->apiKey, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if ($generatedText) {
                        return $this->parseRecipeResponse($generatedText);
                    }
                }

                throw new Exception('API call failed: '.$response->body());
            } catch (Exception $e) {
                $attempt++;
                Log::error("Gemini API call attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'prompt_length' => strlen($prompt),
                ]);

                if ($attempt >= $this->maxRetries) {
                    throw new Exception("Failed to generate recipe after {$this->maxRetries} attempts: ".$e->getMessage());
                }

                sleep(2 ** $attempt); // Exponential backoff
            }
        }

        throw new Exception('Unexpected error in API call');
    }

    /**
     * APIレスポンスを解析
     */
    private function parseRecipeResponse(string $responseText): array
    {
        // デバッグ用：APIレスポンスをログ出力
        \Log::info('Gemini API Response:', ['response' => $responseText]);

        // JSONブロックを抽出
        preg_match('/\{.*\}/s', $responseText, $matches);

        if (empty($matches[0])) {
            \Log::error('No JSON found in response', ['full_response' => $responseText]);
            throw new Exception('No JSON found in response');
        }

        $jsonData = json_decode($matches[0], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('JSON decode error', [
                'json_string' => $matches[0],
                'error' => json_last_error_msg(),
            ]);
            throw new Exception('Invalid JSON in response: '.json_last_error_msg());
        }

        return $jsonData;
    }

    /**
     * レシピデータを検証
     */
    private function validateRecipeData(array $data): array
    {
        $required = ['title', 'cooking_time', 'servings', 'calories', 'tags', 'category', 'ingredients', 'instructions'];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // 数値フィールドの検証
        if (! is_numeric($data['cooking_time']) || $data['cooking_time'] < 5 || $data['cooking_time'] > 120) {
            throw new Exception('Invalid cooking_time: must be between 5 and 120 minutes');
        }

        if (! is_numeric($data['servings']) || $data['servings'] < 1 || $data['servings'] > 6) {
            throw new Exception('Invalid servings: must be between 1 and 6');
        }

        if (! is_numeric($data['calories']) || $data['calories'] < 50 || $data['calories'] > 1500) {
            throw new Exception('Invalid calories: must be between 50 and 1500');
        }

        // 配列フィールドの検証
        if (! is_array($data['ingredients']) || count($data['ingredients']) < 2 || count($data['ingredients']) > 15) {
            throw new Exception('Invalid ingredients: must be array with 2-15 items');
        }

        if (! is_array($data['instructions']) || count($data['instructions']) < 3 || count($data['instructions']) > 15) {
            throw new Exception('Invalid instructions: must be array with 3-15 items');
        }

        return $data;
    }

    /**
     * レシピに食材を関連付け
     */
    private function attachIngredients(Recipe $recipe, array $ingredients): void
    {
        foreach ($ingredients as $ingredientData) {
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $ingredientData['name']],
                ['name' => $ingredientData['name']]
            );

            $recipe->ingredients()->attach($ingredient->id, [
                'quantity' => $ingredientData['amount'],
            ]);
        }
    }
}
