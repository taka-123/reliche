import type { Ingredient } from '~/types/ingredient'
import type { Recipe, RecipeDetail, ApiResponse } from '~/types/recipe'

export const useRecipeApi = () => {
  const api = useApi()

  const searchIngredients = async (query: string): Promise<Ingredient[]> => {
    if (!query.trim()) {
      return []
    }

    const response = await api.get<ApiResponse<Ingredient[]>>(`/ingredients/search?q=${encodeURIComponent(query)}`)
    return response.data.data || []
  }

  const suggestRecipes = async (ingredientIds: number[]): Promise<Recipe[]> => {
    if (ingredientIds.length === 0) {
      throw new Error('食材を選択してください')
    }

    const response = await api.post<ApiResponse<Recipe[]>>('/recipes/suggest', {
      ingredient_ids: ingredientIds
    })
    return response.data.data || []
  }

  const getRecipeDetail = async (id: string | number): Promise<RecipeDetail> => {
    if (!id) {
      throw new Error('レシピIDが指定されていません')
    }

    const response = await api.get<ApiResponse<RecipeDetail>>(`/recipes/${id}`)
    return response.data.data
  }

  return {
    searchIngredients,
    suggestRecipes,
    getRecipeDetail
  }
}