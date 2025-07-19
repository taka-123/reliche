// stores/favorites.ts
import { defineStore } from 'pinia'
import { useApi } from '~/composables/useApi'

interface Recipe {
  id: number
  name: string
  cooking_time: number
  instructions: string[]
  ingredients: Array<{
    id: number
    name: string
    quantity: string
  }>
}

interface FavoriteState {
  favoriteRecipes: Recipe[]
  favoriteRecipeIds: Set<number>
  loading: boolean
  error: string | null
}

export const useFavoritesStore = defineStore('favorites', {
  state: (): FavoriteState => ({
    favoriteRecipes: [],
    favoriteRecipeIds: new Set(),
    loading: false,
    error: null,
  }),

  getters: {
    getFavoriteRecipes: (state) => state.favoriteRecipes,
    isFavorited: (state) => (recipeId: number) => state.favoriteRecipeIds.has(recipeId),
    getFavoriteCount: (state) => state.favoriteRecipes.length,
  },

  actions: {
    /**
     * お気に入りレシピ一覧を取得
     */
    async fetchFavorites() {
      this.loading = true
      this.error = null
      const api = useApi()

      try {
        const response = await api.get('/favorites')
        
        this.favoriteRecipes = response.data.data
        this.favoriteRecipeIds = new Set(this.favoriteRecipes.map(recipe => recipe.id))
        
        return { success: true }
      } catch (error) {
        this.error = error.response?.data?.message || 'お気に入りの取得に失敗しました'
        return { success: false, message: this.error }
      } finally {
        this.loading = false
      }
    },

    /**
     * レシピをお気に入りに追加
     */
    async addToFavorites(recipeId: number) {
      this.loading = true
      this.error = null
      const api = useApi()

      try {
        const response = await api.post('/favorites', {
          recipe_id: recipeId
        })

        this.favoriteRecipeIds.add(recipeId)
        
        return { 
          success: true, 
          message: response.data.message || 'お気に入りに追加しました' 
        }
      } catch (error) {
        const errorMessage = error.response?.data?.message || 'お気に入りの追加に失敗しました'
        this.error = errorMessage
        return { success: false, message: errorMessage }
      } finally {
        this.loading = false
      }
    },

    /**
     * レシピをお気に入りから削除
     */
    async removeFromFavorites(recipeId: number) {
      this.loading = true
      this.error = null
      const api = useApi()

      try {
        const response = await api.delete(`/favorites/${recipeId}`)

        this.favoriteRecipeIds.delete(recipeId)
        this.favoriteRecipes = this.favoriteRecipes.filter(recipe => recipe.id !== recipeId)
        
        return { 
          success: true, 
          message: response.data.message || 'お気に入りから削除しました' 
        }
      } catch (error) {
        const errorMessage = error.response?.data?.message || 'お気に入りの削除に失敗しました'
        this.error = errorMessage
        return { success: false, message: errorMessage }
      } finally {
        this.loading = false
      }
    },

    /**
     * お気に入り状態の切り替え
     */
    async toggleFavorite(recipeId: number) {
      if (this.isFavorited(recipeId)) {
        return await this.removeFromFavorites(recipeId)
      } else {
        return await this.addToFavorites(recipeId)
      }
    },

    /**
     * 特定のレシピのお気に入り状態をチェック
     */
    async checkFavoriteStatus(recipeId: number) {
      const api = useApi()

      try {
        const response = await api.get(`/favorites/${recipeId}/check`)
        
        if (response.data.is_favorited) {
          this.favoriteRecipeIds.add(recipeId)
        } else {
          this.favoriteRecipeIds.delete(recipeId)
        }
        
        return { success: true, is_favorited: response.data.is_favorited }
      } catch (error) {
        return { 
          success: false, 
          message: 'お気に入り状態の確認に失敗しました',
          is_favorited: false 
        }
      }
    },

    /**
     * エラーをクリア
     */
    clearError() {
      this.error = null
    },

    /**
     * ストアをクリア（ログアウト時など）
     */
    clearFavorites() {
      this.favoriteRecipes = []
      this.favoriteRecipeIds = new Set()
      this.loading = false
      this.error = null
    },
  },
})