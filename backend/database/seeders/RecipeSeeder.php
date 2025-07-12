<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\Ingredient;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipesData = json_decode(file_get_contents(base_path('docs/design/05_レシピの初期データ.json')), true);

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
        }
    }
}
