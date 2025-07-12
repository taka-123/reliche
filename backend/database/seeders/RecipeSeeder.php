<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Recipe;
use App\Models\Ingredient;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = base_path('docs/design/05_レシピの初期データ.json');
        
        // ファイル存在チェック
        if (!file_exists($jsonPath)) {
            $this->command->error('Recipe data file not found: ' . $jsonPath);
            return;
        }
        
        $jsonContent = file_get_contents($jsonPath);
        $recipesData = json_decode($jsonContent, true);
        
        // JSON デコードエラーチェック
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON in recipe data file: ' . json_last_error_msg());
            return;
        }
        
        if (!is_array($recipesData)) {
            $this->command->error('Recipe data must be an array');
            return;
        }

        // トランザクション内で実行
        DB::transaction(function () use ($recipesData) {
            foreach ($recipesData as $recipeData) {
            $recipe = Recipe::create([
                'name' => $recipeData['recipe_name'],
                'cooking_time' => $recipeData['cooking_time'],
                'instructions' => $recipeData['instructions'],
            ]);

            foreach ($recipeData['ingredients'] as $ingredientData) {
                $ingredient = Ingredient::firstOrCreate([
                    'name' => $ingredientData['name'],
                ]);

                $recipe->ingredients()->attach($ingredient->id, [
                    'quantity' => $ingredientData['quantity'],
                ]);
            }
        });
    }
}
