export interface AIRecipeIngredient {
  name: string
  amount: string
  nutrition_notes?: string
  cooking_method_tips?: string
}

export interface AIRecipe {
  title: string
  cooking_time: number
  servings: number
  calories: number
  tags: string[]
  category: string
  instructions: string[]
}

export interface NutritionMaster {
  ingredient_name: string
  nutrition_facts: {
    calories_per_100g: number
    protein: number
    fat: number
    carbohydrates: number
    vitamins: Record<string, number>
    minerals: Record<string, number>
  }
  health_benefits: Record<string, string>
  cooking_tips: Record<string, string>
}

export interface AIRecipeResponse {
  success: boolean
  data: {
    recipe: AIRecipe
    recipe_ingredients: AIRecipeIngredient[]
    nutrition_master?: NutritionMaster[]
  }
  message: string
  saved_recipe_id?: number
}

export interface GenerateBasicRecipeOptions {
  category?: string
  save_to_db?: boolean
}

export interface GenerateRecipeByIngredientsOptions {
  ingredients: string[]
  save_to_db?: boolean
}

export interface GenerateRecipeWithConstraintsOptions {
  max_time?: number
  tags?: string[]
  difficulty?: string
  save_to_db?: boolean
}
