export interface User {
  id: number
  name: string
}

export interface RecipeReview {
  id: number
  rating: number
  taste_score?: number
  difficulty_score?: number
  instruction_clarity?: number
  comment?: string
  review_images?: string[]
  average_score: number
  user: {
    id: number
    name: string
  }
  created_at: string
  updated_at?: string
}

export interface ReviewStatistics {
  total_reviews: number
  average_rating: number
  average_taste_score: number
  average_difficulty_score: number
  average_instruction_clarity: number
  rating_distribution: {
    [key: number]: {
      count: number
      percentage: number
    }
  }
}

export interface CreateReviewRequest {
  rating: number
  taste_score?: number
  difficulty_score?: number
  instruction_clarity?: number
  comment?: string
  review_images?: string[]
}

export interface UpdateReviewRequest extends CreateReviewRequest {}

export interface ReviewResponse {
  success: boolean
  message: string
  data?: RecipeReview
  error?: string
}

export interface ReviewListResponse {
  data: RecipeReview[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface ReviewStatisticsResponse {
  data: ReviewStatistics
}
