// API関連の型定義
export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  errors?: Record<string, string[]>
}

// HTTP メソッド
export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

// API リクエスト設定
export interface ApiRequestConfig {
  method?: HttpMethod
  headers?: Record<string, string>
  body?: any
  timeout?: number
}

// ページネーション
export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PaginationMeta
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
}

// エラーレスポンス
export interface ErrorResponse {
  message: string
  errors?: Record<string, string[]>
  status: number
  statusText: string
}