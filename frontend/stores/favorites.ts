import { defineStore } from 'pinia'
import type { 
  Favorite, 
  FavoriteState, 
  FavoritesResponse, 
  FavoriteCheckResponse,
  AddFavoriteRequest,
  FavoriteApiResponse
} from '~/types/favorites'

export const useFavoritesStore = defineStore('favorites', {
  state: (): FavoriteState => ({
    favorites: [],
    loading: false,
    error: null,
  }),

  getters: {
    // ユーザーのお気に入りレシピIDリストを取得
    favoriteRecipeIds: (state): number[] => {
      return state.favorites.map(favorite => favorite.recipe_id)
    },

    // 特定のレシピがお気に入りかどうかチェック
    isFavorited: (state) => (recipeId: number): boolean => {
      return state.favorites.some(favorite => favorite.recipe_id === recipeId)
    },

    // お気に入り数を取得
    favoritesCount: (state): number => {
      return state.favorites.length
    },

    // ローディング状態を取得
    isLoading: (state): boolean => {
      return state.loading
    },

    // エラー状態を取得
    getError: (state): string | null => {
      return state.error
    },
  },

  actions: {
    /**
     * エラーをクリア
     */
    clearError() {
      this.error = null
    },

    /**
     * ローディング状態を設定
     */
    setLoading(loading: boolean) {
      this.loading = loading
    },

    /**
     * お気に入り一覧を取得
     */
    async fetchFavorites(): Promise<void> {
      try {
        this.setLoading(true)
        this.clearError()

        const { $api } = useNuxtApp()
        const response = await $api<FavoritesResponse>('/api/favorites', {
          method: 'GET',
        })

        if (response.success && response.data) {
          this.favorites = response.data
        } else {
          throw new Error(response.message || 'お気に入り一覧の取得に失敗しました')
        }
      } catch (error: any) {
        this.error = error.message || 'お気に入り一覧の取得に失敗しました'
        console.error('お気に入り一覧取得エラー:', error)
      } finally {
        this.setLoading(false)
      }
    },

    /**
     * お気に入りに追加
     */
    async addToFavorites(recipeId: number): Promise<boolean> {
      try {
        this.setLoading(true)
        this.clearError()

        // 既にお気に入りに登録されているかチェック
        if (this.isFavorited(recipeId)) {
          this.error = 'このレシピは既にお気に入りに登録されています'
          return false
        }

        const { $api } = useNuxtApp()
        const requestData: AddFavoriteRequest = { recipe_id: recipeId }
        
        const response = await $api<FavoriteApiResponse<Favorite>>('/api/favorites', {
          method: 'POST',
          body: requestData,
        })

        if (response.success && response.data) {
          // ローカル状態を更新
          this.favorites.unshift(response.data)
          return true
        } else {
          throw new Error(response.message || 'お気に入りの追加に失敗しました')
        }
      } catch (error: any) {
        this.error = error.message || 'お気に入りの追加に失敗しました'
        console.error('お気に入り追加エラー:', error)
        return false
      } finally {
        this.setLoading(false)
      }
    },

    /**
     * お気に入りから削除
     */
    async removeFromFavorites(recipeId: number): Promise<boolean> {
      try {
        this.setLoading(true)
        this.clearError()

        const { $api } = useNuxtApp()
        const response = await $api<FavoriteApiResponse>(`/api/favorites/${recipeId}`, {
          method: 'DELETE',
        })

        if (response.success) {
          // ローカル状態を更新
          this.favorites = this.favorites.filter(favorite => favorite.recipe_id !== recipeId)
          return true
        } else {
          throw new Error(response.message || 'お気に入りの削除に失敗しました')
        }
      } catch (error: any) {
        this.error = error.message || 'お気に入りの削除に失敗しました'
        console.error('お気に入り削除エラー:', error)
        return false
      } finally {
        this.setLoading(false)
      }
    },

    /**
     * お気に入り状態をトグル（追加/削除）
     */
    async toggleFavorite(recipeId: number): Promise<boolean> {
      if (this.isFavorited(recipeId)) {
        return await this.removeFromFavorites(recipeId)
      } else {
        return await this.addToFavorites(recipeId)
      }
    },

    /**
     * 特定のレシピのお気に入り状態をチェック
     */
    async checkFavoriteStatus(recipeId: number): Promise<boolean> {
      try {
        this.clearError()

        const { $api } = useNuxtApp()
        const response = await $api<FavoriteCheckResponse>(`/api/favorites/check/${recipeId}`, {
          method: 'GET',
        })

        if (response.success && response.data) {
          return response.data.is_favorited
        } else {
          throw new Error(response.message || 'お気に入り状態の取得に失敗しました')
        }
      } catch (error: any) {
        this.error = error.message || 'お気に入り状態の取得に失敗しました'
        console.error('お気に入り状態チェックエラー:', error)
        return false
      }
    },

    /**
     * 状態をリセット
     */
    resetState() {
      this.favorites = []
      this.loading = false
      this.error = null
    },
  },
})