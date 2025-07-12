export interface Recipe {
  id: number
  name: string
  cooking_time: number
  missing_count: number
  status: string
}

export interface RecipeDetail extends Recipe {
  instructions: string[]
  ingredients: RecipeIngredient[]
  created_at: string
  updated_at: string
}

export interface RecipeIngredient {
  id: number
  name: string
  quantity: string
}

export interface ApiResponse<T> {
  data: T
  message?: string
}