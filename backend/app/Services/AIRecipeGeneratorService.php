<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Recipe;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AIRecipeGeneratorService
{
    private ?string $apiKey;

    private string $model;

    private string $baseUrl;

    private int $maxRetries;

    private int $timeout;

    private int $cacheTtl;

    private bool $useProModel;

    private string $imageGenerationUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash-lite');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->maxRetries = config('ai.recipe.max_retries', 3);
        $this->timeout = config('ai.recipe.timeout', 30);
        $this->cacheTtl = config('ai.recipe.cache_ttl', 3600);
        $this->useProModel = false; // デフォルトはFlash-Lite
        $this->imageGenerationUrl = config('services.mcp.image_generation_url', 'https://mcp-creatify-lipsync-20250719-010824-a071b7b8-820994673238.us-central1.run.app/t2i/fal/rundiffusion/photo-flux');

        // テスト環境またはCI環境ではAPIキーチェックをスキップ
        if (app()->runningUnitTests() || app()->environment('testing') || ! empty(env('CI'))) {
            return;
        }

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }
    }

    /**
     * Pro モデル使用を設定
     */
    public function useProModel(bool $usePro = true): self
    {
        $this->useProModel = $usePro;
        if ($usePro) {
            $this->model = 'gemini-2.5-pro';
        } else {
            $this->model = config('services.gemini.model', 'gemini-2.5-flash-lite');
        }

        return $this;
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

        // 新構造と旧構造を自動判別
        if (isset($validatedData['recipe'])) {
            // 新構造（Pro/Flash版）
            $recipeInfo = $validatedData['recipe'];
            $ingredients = $validatedData['recipe_ingredients'] ?? [];
        } else {
            // 旧構造（後方互換性）
            $recipeInfo = $validatedData;
            $ingredients = $validatedData['ingredients'] ?? [];
        }

        // レシピの基本情報を保存
        $recipe = Recipe::create([
            'name' => $recipeInfo['title'],
            'cooking_time' => $recipeInfo['cooking_time'],
            'servings' => $recipeInfo['servings'],
            'calories' => $recipeInfo['calories'],
            'tags' => $recipeInfo['tags'],
            'category' => $recipeInfo['category'],
            'instructions' => $recipeInfo['instructions'],
            'source' => 'ai_generated',
        ]);

        // 食材を保存（新構造なら栄養メモ付き、旧構造なら通常）
        if (isset($validatedData['recipe_ingredients'])) {
            $this->attachIngredientsWithNutrition($recipe, $ingredients);
        } else {
            $this->attachIngredients($recipe, $ingredients);
        }

        // 栄養マスターデータがある場合は保存
        if (isset($validatedData['nutrition_master'])) {
            $this->saveNutritionMasterData($validatedData['nutrition_master']);
        }

        // レシピ画像を生成して保存
        $imageUrl = $this->generateRecipeImage(
            $recipeInfo['title'],
            $recipeInfo['description'] ?? '',
            $ingredients
        );
        
        if ($imageUrl) {
            $recipe->update(['image_url' => $imageUrl]);
            Log::info('Recipe image generated and saved', [
                'recipe_id' => $recipe->id,
                'image_url' => $imageUrl
            ]);
        }

        return $recipe;
    }

    /**
     * 基本プロンプトを構築
     */
    private function buildBasicPrompt(?string $category = null): string
    {
        $basePrompt = $this->getBasePrompt();

        $theme = $category ? "カテゴリ: {$category}の家庭料理" : '日本の家庭料理';
        $mustUseIngredients = [];
        $additionalConstraints = [];

        return $this->applyDynamicParameters($basePrompt, $theme, $mustUseIngredients, $additionalConstraints);
    }

    /**
     * 食材指定プロンプトを構築
     */
    private function buildIngredientsPrompt(array $ingredients): string
    {
        $basePrompt = $this->getBasePrompt();

        $theme = '指定食材を活用した家庭料理';
        $mustUseIngredients = $ingredients;
        $additionalConstraints = ['指定された食材を必ず使用すること'];

        return $this->applyDynamicParameters($basePrompt, $theme, $mustUseIngredients, $additionalConstraints);
    }

    /**
     * 制約条件プロンプトを構築
     */
    private function buildConstraintsPrompt(array $constraints): string
    {
        $basePrompt = $this->getBasePrompt();

        $theme = '制約条件を満たす家庭料理';
        $mustUseIngredients = [];
        $additionalConstraints = [];

        if (isset($constraints['max_time'])) {
            $additionalConstraints[] = "調理時間{$constraints['max_time']}分以内";
        }
        if (isset($constraints['tags'])) {
            $tags = implode(', ', $constraints['tags']);
            $additionalConstraints[] = "タグ: {$tags}";
        }
        if (isset($constraints['difficulty'])) {
            $additionalConstraints[] = "難易度: {$constraints['difficulty']}";
        }

        return $this->applyDynamicParameters($basePrompt, $theme, $mustUseIngredients, $additionalConstraints);
    }

    /**
     * 動的パラメータをプロンプトに適用
     */
    private function applyDynamicParameters(string $basePrompt, string $theme, array $mustUseIngredients, array $additionalConstraints): string
    {
        $dynamicSection = "\n\n# Generation Parameters\n";
        $dynamicSection .= "- THEME: {$theme}\n";

        if (! empty($mustUseIngredients)) {
            $ingredientsList = '["'.implode('", "', $mustUseIngredients).'"]';
            $dynamicSection .= "- MUST_USE_INGREDIENTS: {$ingredientsList}\n";
        } else {
            $dynamicSection .= "- MUST_USE_INGREDIENTS: []\n";
        }

        if (! empty($additionalConstraints)) {
            $constraintsList = '["'.implode('", "', $additionalConstraints).'"]';
            $dynamicSection .= "- ADDITIONAL_CONSTRAINTS: {$constraintsList}\n";
        } else {
            $dynamicSection .= "- ADDITIONAL_CONSTRAINTS: []\n";
        }

        return $basePrompt.$dynamicSection;
    }

    /**
     * 基本プロンプトテンプレート
     */
    private function getBasePrompt(): string
    {
        if ($this->useProModel) {
            return $this->getProPrompt();
        } else {
            return $this->getFlashPrompt();
        }
    }

    /**
     * Gemini 2.5 Pro用プロンプト (高品質・詳細版)
     */
    private function getProPrompt(): string
    {
        return '# AI Persona Configuration
あなたは、管理栄養士の資格を持つ経験豊富な料理研究家であり、同時に構造化データのエキスパートです。あなたの使命は、科学的根拠に基づいた「美味しく、健康的で、再現性の高い」レシピを考案し、その全てを寸分違わず指定されたJSON形式で出力することです。

# Core Task
以下の指示と制約に基づき、日本の家庭料理のレシピを1つ考案し、指定のJSON形式で出力してください。

# Strict Constraints
- 調理時間: 5〜60分
- 人数: 1〜6人
- カロリー: 100〜800kcal/人
- 材料数: 2〜15個
- 手順数: 3〜10ステップ
- 分量: 「適量」「少々」などの曖昧な表現は禁止。具体的な計量単位(g, ml, 大さじ)で記述。
- 食材: 日本の一般的なスーパーで入手可能なもののみ。
- 調理器具: 一般的な家庭にあるもの（フライパン、鍋、電子レンジ等）のみ。
- 安全性: 食中毒リスクを避けるため、肉や魚介類には中心部まで加熱する指示を必ず含める。

# Execution Steps (Chain of Thought)
1. **Analyze & Plan**: 上記の制約条件を完全に理解し、全ての条件を満たすレシピのコンセプトを定義します。
2. **Create Recipe**: 創造的かつ論理的にレシピを構築します。調理科学に基づき、なぜその手順で美味しくなるのかを考慮します。
3. **Nutrition Analysis**: 各食材の栄養価を詳細に分析します。`nutrition_master`セクションでは、食材の一般的な栄養情報、健康効果、調理法による栄養変化を専門的知識に基づいて記述します。`recipe_ingredients`セクションでは、そのレシピにおける各食材の役割や栄養的なポイントを記述します。
4. **Format to JSON**: 全ての情報を、後述の`OUTPUT_JSON_STRUCTURE`に寸分の狂いなくマッピングします。JSON以外の説明文、コメント、思考過程は絶対に出力に含めないでください。
5. **Self-Correction**: 出力するJSONが、指定された構造、データ型、制約条件の全てを完璧に満たしているか最終検証します。

# OUTPUT_JSON_STRUCTURE (この構造とデータ型を厳守)
{
  "recipe": {
    "title": "string",
    "cooking_time": "integer (分)",
    "servings": "integer (人前)",
    "calories": "integer (1人前あたりのkcal)",
    "tags": ["string"],
    "category": "string (例: 和食, 洋食, 中華)",
    "instructions": ["string"]
  },
  "recipe_ingredients": [
    {
      "name": "string",
      "amount": "string (例: 300g, 大さじ2)",
      "nutrition_notes": "string (このレシピにおける栄養的な役割や特記事項)",
      "cooking_method_tips": "string (この調理法での栄養素の損失を防ぐコツなど)"
    }
  ],
  "nutrition_master": [
    {
      "ingredient_name": "string",
      "nutrition_facts": {
        "calories_per_100g": "integer",
        "protein": "float",
        "fat": "float",
        "carbohydrates": "float",
        "vitamins": {},
        "minerals": {}
      },
      "health_benefits": {},
      "cooking_tips": {}
    }
  ]
}';
    }

    /**
     * Gemini 2.5 Flash-Lite用プロンプト (簡潔・高速版)
     */
    private function getFlashPrompt(): string
    {
        return '# Role
あなたは高速なレシピ生成AIです。指示された条件に基づき、必須情報を素早く正確にJSON形式で出力します。

# Rules
- 必須項目: `recipe`と`recipe_ingredients`は必ず生成する。
- 省略項目: `nutrition_master`は生成しない。`recipe_ingredients`内の`nutrition_notes`と`cooking_method_tips`は簡潔な一文、または空文字列""でよい。
- 厳守事項:
    - 調理時間: 5-60分
    - 人数: 1-6人
    - カロリー: 100-800kcal/人
    - 材料数: 2-10個
    - 手順数: 3-8ステップ
    - 分量は具体的に。曖昧な表現は禁止。
    - 出力はJSONのみ。他のテキストは一切含めない。

# OUTPUT_JSON_STRUCTURE (この構造を厳守)
{
  "recipe": {
    "title": "string",
    "cooking_time": "integer (分)",
    "servings": "integer (人前)",
    "calories": "integer (1人前あたりのkcal)",
    "tags": ["string"],
    "category": "string (例: 和食, 洋食, 中華)",
    "instructions": ["string"]
  },
  "recipe_ingredients": [
    {
      "name": "string",
      "amount": "string (例: 1個, 150g)",
      "nutrition_notes": "string (簡潔なメモ、または空文字列)",
      "cooking_method_tips": "string (簡潔なメモ、または空文字列)"
    }
  ]
}';
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
        // 実際のAPI呼び出し時にAPIキーをチェック（テスト・CI環境以外）
        if (empty($this->apiKey) && ! app()->runningUnitTests() && ! app()->environment('testing') && empty(env('CI'))) {
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
        // 新しいJSON構造に対応
        if (isset($data['recipe'])) {
            // Pro版またはFlash版の新構造
            $recipeData = $data['recipe'];
            $required = ['title', 'cooking_time', 'servings', 'calories', 'tags', 'category', 'instructions'];
        } else {
            // 旧構造（後方互換性）
            $recipeData = $data;
            $required = ['title', 'cooking_time', 'servings', 'calories', 'tags', 'category', 'ingredients', 'instructions'];
        }

        foreach ($required as $field) {
            if (! isset($recipeData[$field])) {
                throw new Exception("Missing required field in recipe: {$field}");
            }
        }

        // 数値フィールドの検証
        if (! is_numeric($recipeData['cooking_time']) || $recipeData['cooking_time'] < 5 || $recipeData['cooking_time'] > 120) {
            throw new Exception('Invalid cooking_time: must be between 5 and 120 minutes');
        }

        if (! is_numeric($recipeData['servings']) || $recipeData['servings'] < 1 || $recipeData['servings'] > 6) {
            throw new Exception('Invalid servings: must be between 1 and 6');
        }

        if (! is_numeric($recipeData['calories']) || $recipeData['calories'] < 50 || $recipeData['calories'] > 1500) {
            throw new Exception('Invalid calories: must be between 50 and 1500');
        }

        // recipe_ingredients の検証
        if (isset($data['recipe_ingredients'])) {
            if (! is_array($data['recipe_ingredients']) || count($data['recipe_ingredients']) < 2 || count($data['recipe_ingredients']) > 15) {
                throw new Exception('Invalid recipe_ingredients: must be array with 2-15 items');
            }
        }

        // 配列フィールドの検証
        if (! is_array($recipeData['instructions']) || count($recipeData['instructions']) < 3 || count($recipeData['instructions']) > 15) {
            throw new Exception('Invalid instructions: must be array with 3-15 items');
        }

        return $data;
    }

    /**
     * レシピに食材を関連付け（栄養メモ付き）
     */
    private function attachIngredientsWithNutrition(Recipe $recipe, array $ingredients): void
    {
        foreach ($ingredients as $ingredientData) {
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $ingredientData['name']],
                ['name' => $ingredientData['name']]
            );

            $pivotData = [
                'quantity' => $ingredientData['amount'],
                'nutrition_notes' => $ingredientData['nutrition_notes'] ?? '',
                'cooking_method_tips' => $ingredientData['cooking_method_tips'] ?? '',
            ];

            $recipe->ingredients()->attach($ingredient->id, $pivotData);
        }
    }

    /**
     * 旧形式の食材関連付け（後方互換性）
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

    /**
     * 栄養マスターデータを保存
     */
    private function saveNutritionMasterData(array $nutritionMasters): void
    {
        foreach ($nutritionMasters as $nutritionData) {
            \App\Models\IngredientNutrition::updateOrCreate(
                ['ingredient_name' => $nutritionData['ingredient_name']],
                [
                    'nutrition_facts' => $nutritionData['nutrition_facts'],
                    'health_benefits' => $nutritionData['health_benefits'],
                    'cooking_tips' => $nutritionData['cooking_tips'],
                ]
            );
        }
    }

    /**
     * レシピ画像を生成
     */
    public function generateRecipeImage(string $recipeName, string $description, array $ingredients = []): ?string
    {
        try {
            // レシピの詳細情報から画像生成用のプロンプトを作成
            $prompt = $this->createImagePrompt($recipeName, $description, $ingredients);
            
            Log::info('Generating recipe image', [
                'recipe_name' => $recipeName,
                'prompt' => $prompt
            ]);

            // MCP経由で画像生成API呼び出し
            $response = Http::timeout($this->timeout)
                ->post($this->imageGenerationUrl, [
                    'prompt' => $prompt,
                    'image_size' => 'landscape_4_3',
                    'num_inference_steps' => 28,
                    'guidance_scale' => 3.5,
                    'num_images' => 1,
                    'enable_safety_checker' => true,
                    'safety_tolerance' => 2
                ]);

            if (!$response->successful()) {
                Log::error('Image generation failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();
            
            if (empty($data['images']) || !isset($data['images'][0]['url'])) {
                Log::error('No image URL in response', ['response' => $data]);
                return null;
            }

            $imageUrl = $data['images'][0]['url'];
            
            // 画像をダウンロードして保存
            return $this->downloadAndSaveImage($imageUrl, $recipeName);
            
        } catch (Exception $e) {
            Log::error('Recipe image generation failed', [
                'recipe_name' => $recipeName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 画像生成用のプロンプトを作成
     */
    private function createImagePrompt(string $recipeName, string $description, array $ingredients): string
    {
        $ingredientList = !empty($ingredients) ? implode(', ', array_column($ingredients, 'name')) : '';
        
        $prompt = "Professional food photography of {$recipeName}. ";
        $prompt .= "Beautiful, appetizing dish presentation. ";
        $prompt .= "{$description} ";
        
        if ($ingredientList) {
            $prompt .= "Made with {$ingredientList}. ";
        }
        
        $prompt .= "High-quality, well-lit, restaurant-style plating. ";
        $prompt .= "Clean white background, natural lighting, 4K resolution, ";
        $prompt .= "professional culinary photography, appetizing colors, ";
        $prompt .= "detailed textures, mouth-watering presentation";
        
        return $prompt;
    }

    /**
     * 画像をダウンロードして保存
     */
    private function downloadAndSaveImage(string $imageUrl, string $recipeName): ?string
    {
        try {
            $imageResponse = Http::timeout(30)->get($imageUrl);
            
            if (!$imageResponse->successful()) {
                Log::error('Failed to download image', ['url' => $imageUrl]);
                return null;
            }

            $imageContent = $imageResponse->body();
            $fileName = 'recipes/' . Str::slug($recipeName) . '_' . time() . '.jpg';
            
            // 画像をストレージに保存
            if (Storage::disk('public')->put($fileName, $imageContent)) {
                return Storage::disk('public')->url($fileName);
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('Failed to download and save image', [
                'url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
