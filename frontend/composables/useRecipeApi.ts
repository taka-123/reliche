import type { Ingredient } from '~/types/ingredient'
import type { Recipe, RecipeDetail, ApiResponse } from '~/types/recipe'
import type {
  AIRecipeResponse,
  GenerateBasicRecipeOptions,
  GenerateRecipeByIngredientsOptions,
  GenerateRecipeWithConstraintsOptions,
} from '~/types/aiRecipe'

export const useRecipeApi = () => {
  const api = useApi()

  const searchIngredients = async (query: string): Promise<Ingredient[]> => {
    if (!query.trim()) {
      return []
    }

    const response = await api.get<ApiResponse<Ingredient[]>>(
      `/ingredients/search?q=${encodeURIComponent(query)}`,
    )
    return response.data.data || []
  }

  const getAllRecipes = async (): Promise<Recipe[]> => {
    // 全レシピを表示するため、空の食材配列を送信
    const response = await api.post<ApiResponse<Recipe[]>>('/recipes/suggest', {
      ingredient_ids: [],
    })
    return response.data.data || []
  }

  const suggestRecipes = async (ingredientIds: number[]): Promise<Recipe[]> => {
    const response = await api.post<ApiResponse<Recipe[]>>('/recipes/suggest', {
      ingredient_ids: ingredientIds,
    })
    return response.data.data || []
  }

  const getRecipeDetail = async (
    id: string | number,
  ): Promise<RecipeDetail> => {
    if (!id) {
      throw new Error('レシピIDが指定されていません')
    }

    const response = await api.get<ApiResponse<RecipeDetail>>(`/recipes/${id}`)
    return response.data.data
  }

  const generateBasicRecipe = async (
    options: GenerateBasicRecipeOptions = {},
  ): Promise<AIRecipeResponse> => {
    const response = await api.post<AIRecipeResponse>(
      '/ai-recipes/generate',
      options,
    )
    return response.data
  }

  const generateRecipeByIngredients = async (
    options: GenerateRecipeByIngredientsOptions,
  ): Promise<AIRecipeResponse> => {
    const response = await api.post<AIRecipeResponse>(
      '/ai-recipes/generate/ingredients',
      options,
    )
    return response.data
  }

  const generateRecipeWithConstraints = async (
    options: GenerateRecipeWithConstraintsOptions = {},
  ): Promise<AIRecipeResponse> => {
    const response = await api.post<AIRecipeResponse>(
      '/ai-recipes/generate/constraints',
      options,
    )
    return response.data
  }

  return {
    searchIngredients,
    getAllRecipes,
    suggestRecipes,
    getRecipeDetail,
    generateBasicRecipe,
    generateRecipeByIngredients,
    generateRecipeWithConstraints,
  }
}
