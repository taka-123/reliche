import type {
  RecipeReview,
  ReviewStatistics,
  CreateReviewRequest,
  UpdateReviewRequest,
  ReviewResponse,
  ReviewListResponse,
  ReviewStatisticsResponse,
} from '~/types/review'

export const useReviewApi = () => {
  const api = useApi()

  /**
   * レシピのレビュー一覧を取得
   */
  const getReviews = async (
    recipeId: string | number,
    page = 1
  ): Promise<ReviewListResponse> => {
    const response = await api.get<ReviewListResponse>(
      `/recipes/${recipeId}/reviews?page=${page}`
    )
    return response.data
  }

  /**
   * レシピの評価統計を取得
   */
  const getStatistics = async (
    recipeId: string | number
  ): Promise<ReviewStatistics> => {
    const response = await api.get<ReviewStatisticsResponse>(
      `/recipes/${recipeId}/reviews/statistics`
    )
    return response.data.data
  }

  /**
   * レビューを投稿
   */
  const createReview = async (
    recipeId: string | number,
    reviewData: CreateReviewRequest
  ): Promise<RecipeReview> => {
    const response = await api.post<ReviewResponse>(
      `/recipes/${recipeId}/reviews`,
      reviewData
    )

    if (!response.data.success) {
      throw new Error(response.data.message || 'レビューの投稿に失敗しました')
    }

    if (!response.data.data) {
      throw new Error('レビューデータが取得できませんでした')
    }

    return response.data.data
  }

  /**
   * レビューを更新
   */
  const updateReview = async (
    recipeId: string | number,
    reviewId: number,
    reviewData: UpdateReviewRequest
  ): Promise<RecipeReview> => {
    const response = await api.put<ReviewResponse>(
      `/recipes/${recipeId}/reviews/${reviewId}`,
      reviewData
    )

    if (!response.data.success) {
      throw new Error(response.data.message || 'レビューの更新に失敗しました')
    }

    if (!response.data.data) {
      throw new Error('レビューデータが取得できませんでした')
    }

    return response.data.data
  }

  /**
   * レビューを削除
   */
  const deleteReview = async (
    recipeId: string | number,
    reviewId: number
  ): Promise<void> => {
    const response = await api.delete<ReviewResponse>(
      `/recipes/${recipeId}/reviews/${reviewId}`
    )

    if (!response.data.success) {
      throw new Error(response.data.message || 'レビューの削除に失敗しました')
    }
  }

  /**
   * レビュー詳細を取得
   */
  const getReview = async (
    recipeId: string | number,
    reviewId: number
  ): Promise<RecipeReview> => {
    const response = await api.get<{ data: RecipeReview }>(
      `/recipes/${recipeId}/reviews/${reviewId}`
    )
    return response.data.data
  }

  return {
    getReviews,
    getStatistics,
    createReview,
    updateReview,
    deleteReview,
    getReview,
  }
}
