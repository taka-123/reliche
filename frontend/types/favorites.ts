// お気に入り機能の型定義
export interface Favorite {
  id: number
  user_id: number
  recipe_id: number
  created_at: string
  updated_at: string
}

export interface FavoriteState {
  favorites: Favorite[]
  loading: boolean
  error: string | null
}

export interface FavoritesResponse {
  success: boolean
  message: string
  data: Favorite[]
  meta?: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface FavoriteCheckResponse {
  success: boolean
  message: string
  data: {
    recipe_id: number
    is_favorited: boolean
  }
}

export interface AddFavoriteRequest {
  recipe_id: number
}

export interface RemoveFavoriteRequest {
  recipe_id: number
}

// レシピ関連の型定義（将来の拡張用）
export interface Recipe {
  id: number
  title: string
  description?: string
  image_url?: string
  ingredients?: string[]
  instructions?: string[]
  cooking_time?: number
  servings?: number
  created_at: string
  updated_at: string
}

// お気に入り機能のAPI呼び出し結果
export interface FavoriteApiResponse<T = unknown> {
  success: boolean
  message: string
  data?: T
  errors?: Record<string, string[]>
}
