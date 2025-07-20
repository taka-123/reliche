// 認証関連の型定義
export interface User {
  id: number
  name: string
  email: string
  email_verified_at?: string | null
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterCredentials {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  success: boolean
  message: string
  user?: User
  token?: string
}

export interface ValidationErrors {
  name?: string[]
  email?: string[]
  password?: string[]
  password_confirmation?: string[]
}

export interface ApiError {
  message: string
  errors?: ValidationErrors
  status?: number
}

// フォームの状態管理用
export interface FormState {
  loading: boolean
  errors: ValidationErrors
  touched: Record<string, boolean>
}

// バリデーションルール
export interface ValidationRule {
  required?: boolean
  minLength?: number
  maxLength?: number
  pattern?: RegExp
  message: string
}

export interface ValidationRules {
  [key: string]: ValidationRule[]
}