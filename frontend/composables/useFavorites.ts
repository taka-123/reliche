import { useFavoritesStore } from '~/stores/favorites'

/**
 * お気に入り機能を管理するcomposable
 */
export function useFavorites() {
  const favoritesStore = useFavoritesStore()

  // リアクティブな状態
  const favorites = computed(() => favoritesStore.favorites)
  const favoriteRecipeIds = computed(() => favoritesStore.favoriteRecipeIds)
  const isLoading = computed(() => favoritesStore.isLoading)
  const error = computed(() => favoritesStore.getError)
  const favoritesCount = computed(() => favoritesStore.favoritesCount)

  /**
   * 特定のレシピがお気に入りかどうかチェック
   */
  const isFavorited = (recipeId: number): boolean => {
    return favoritesStore.isFavorited(recipeId)
  }

  /**
   * お気に入り一覧を取得
   */
  const fetchFavorites = async (): Promise<void> => {
    await favoritesStore.fetchFavorites()
  }

  /**
   * お気に入りに追加
   */
  const addToFavorites = async (recipeId: number): Promise<boolean> => {
    const result = await favoritesStore.addToFavorites(recipeId)
    
    if (result) {
      // 成功時の通知などを追加可能
      console.log(`レシピ ${recipeId} をお気に入りに追加しました`)
    }
    
    return result
  }

  /**
   * お気に入りから削除
   */
  const removeFromFavorites = async (recipeId: number): Promise<boolean> => {
    const result = await favoritesStore.removeFromFavorites(recipeId)
    
    if (result) {
      // 成功時の通知などを追加可能
      console.log(`レシピ ${recipeId} をお気に入りから削除しました`)
    }
    
    return result
  }

  /**
   * お気に入り状態をトグル
   */
  const toggleFavorite = async (recipeId: number): Promise<boolean> => {
    const wasAlreadyFavorited = isFavorited(recipeId)
    const result = await favoritesStore.toggleFavorite(recipeId)
    
    if (result) {
      const action = wasAlreadyFavorited ? '削除' : '追加'
      console.log(`レシピ ${recipeId} のお気に入り${action}が完了しました`)
    }
    
    return result
  }

  /**
   * エラーをクリア
   */
  const clearError = (): void => {
    favoritesStore.clearError()
  }

  /**
   * 状態をリセット
   */
  const resetState = (): void => {
    favoritesStore.resetState()
  }

  /**
   * デバウンス付きのお気に入りトグル
   * 短期間での連続クリックを防ぐ
   */
  const debouncedToggleFavorite = (() => {
    let timeoutId: NodeJS.Timeout | null = null
    
    return async (recipeId: number, delay: number = 300): Promise<boolean> => {
      return new Promise((resolve) => {
        if (timeoutId) {
          clearTimeout(timeoutId)
        }
        
        timeoutId = setTimeout(async () => {
          const result = await toggleFavorite(recipeId)
          resolve(result)
        }, delay)
      })
    }
  })()

  return {
    // State
    favorites: readonly(favorites),
    favoriteRecipeIds: readonly(favoriteRecipeIds),
    isLoading: readonly(isLoading),
    error: readonly(error),
    favoritesCount: readonly(favoritesCount),
    
    // Actions
    isFavorited,
    fetchFavorites,
    addToFavorites,
    removeFromFavorites,
    toggleFavorite,
    debouncedToggleFavorite,
    clearError,
    resetState,
  }
}