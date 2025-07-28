export interface RecipeMedia {
  id: number
  recipe_id: number
  user_id: number
  media_type: 'image' | 'video'
  file_path: string
  original_filename: string
  file_size: number
  mime_type: string
  metadata: {
    width?: number
    height?: number
    orientation?: 'landscape' | 'portrait'
    duration?: number
    resolution?: string
    error?: string
  }
  description: string | null
  is_approved: boolean
  approved_by: number | null
  approved_at: string | null
  full_url: string
  human_readable_file_size: string
  user: {
    id: number
    name: string
  }
  approver?: {
    id: number
    name: string
  }
  created_at: string
  updated_at: string
}

export interface MediaUploadRequest {
  file: File
  description?: string
}

export interface MediaUpdateRequest {
  description?: string
}

export interface MediaListResponse {
  data: RecipeMedia[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface MediaResponse {
  data: RecipeMedia
}

export interface MediaUploadResponse {
  message: string
  data: RecipeMedia
}

export interface MediaErrorResponse {
  error: string
  details?: Record<string, string[]>
}

export interface PendingMediaResponse {
  data: RecipeMedia[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
