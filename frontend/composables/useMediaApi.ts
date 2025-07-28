import type {
  MediaListResponse,
  MediaResponse,
  MediaUploadRequest,
  MediaUploadResponse,
  MediaUpdateRequest,
  PendingMediaResponse,
} from '~/types/media'

export const useMediaApi = () => {
  const { $api } = useNuxtApp()

  /**
   * レシピのメディア一覧を取得
   */
  const getRecipeMedia = async (
    recipeId: number,
    page: number = 1
  ): Promise<MediaListResponse> => {
    try {
      const response = await $api<MediaListResponse>(
        `/recipes/${recipeId}/media?page=${page}`
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to fetch recipe media:', error)
      throw error
    }
  }

  /**
   * メディア詳細を取得
   */
  const getMediaDetail = async (
    recipeId: number,
    mediaId: number
  ): Promise<MediaResponse> => {
    try {
      const response = await $api<MediaResponse>(
        `/recipes/${recipeId}/media/${mediaId}`
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to fetch media detail:', error)
      throw error
    }
  }

  /**
   * メディアをアップロード
   */
  const uploadMedia = async (
    recipeId: number,
    data: MediaUploadRequest
  ): Promise<MediaUploadResponse> => {
    try {
      const formData = new FormData()
      formData.append('file', data.file)
      if (data.description) {
        formData.append('description', data.description)
      }

      const response = await $api<MediaUploadResponse>(
        `/recipes/${recipeId}/media`,
        {
          method: 'POST',
          body: formData,
          headers: {
            Accept: 'application/json',
            // Content-Typeは設定しない（FormDataの場合、ブラウザが自動設定）
          },
        }
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to upload media:', error)
      throw error
    }
  }

  /**
   * メディア情報を更新
   */
  const updateMedia = async (
    recipeId: number,
    mediaId: number,
    data: MediaUpdateRequest
  ): Promise<MediaResponse> => {
    try {
      const response = await $api<MediaResponse>(
        `/recipes/${recipeId}/media/${mediaId}`,
        {
          method: 'PUT',
          body: data,
        }
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to update media:', error)
      throw error
    }
  }

  /**
   * メディアを削除
   */
  const deleteMedia = async (
    recipeId: number,
    mediaId: number
  ): Promise<{ message: string }> => {
    try {
      const response = await $api<{ message: string }>(
        `/recipes/${recipeId}/media/${mediaId}`,
        {
          method: 'DELETE',
        }
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to delete media:', error)
      throw error
    }
  }

  /**
   * メディアを承認（管理者のみ）
   */
  const approveMedia = async (
    recipeId: number,
    mediaId: number
  ): Promise<MediaResponse> => {
    try {
      const response = await $api<MediaResponse>(
        `/recipes/${recipeId}/media/${mediaId}/approve`,
        {
          method: 'POST',
        }
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to approve media:', error)
      throw error
    }
  }

  /**
   * メディア承認を取り消し（管理者のみ）
   */
  const unapproveMedia = async (
    recipeId: number,
    mediaId: number
  ): Promise<MediaResponse> => {
    try {
      const response = await $api<MediaResponse>(
        `/recipes/${recipeId}/media/${mediaId}/unapprove`,
        {
          method: 'POST',
        }
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to unapprove media:', error)
      throw error
    }
  }

  /**
   * 承認待ちメディア一覧を取得（管理者のみ）
   */
  const getPendingMedia = async (
    page: number = 1
  ): Promise<PendingMediaResponse> => {
    try {
      const response = await $api<PendingMediaResponse>(
        `/media/pending?page=${page}`
      )
      return response
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Failed to fetch pending media:', error)
      throw error
    }
  }

  /**
   * ファイル拡張子からメディアタイプを判定
   */
  const getMediaTypeFromFile = (file: File): 'image' | 'video' | 'unknown' => {
    const mimeType = file.type
    if (mimeType.startsWith('image/')) {
      return 'image'
    } else if (mimeType.startsWith('video/')) {
      return 'video'
    }
    return 'unknown'
  }

  /**
   * ファイルサイズのバリデーション
   */
  const validateFileSize = (file: File): { valid: boolean; error?: string } => {
    const mediaType = getMediaTypeFromFile(file)
    const sizeMB = file.size / (1024 * 1024)

    if (mediaType === 'image' && sizeMB > 5) {
      return { valid: false, error: '画像ファイルは5MB以下にしてください' }
    }

    if (mediaType === 'video' && sizeMB > 50) {
      return { valid: false, error: '動画ファイルは50MB以下にしてください' }
    }

    if (mediaType === 'unknown') {
      return { valid: false, error: 'サポートされていないファイル形式です' }
    }

    return { valid: true }
  }

  /**
   * ファイル形式のバリデーション
   */
  const validateFileType = (file: File): { valid: boolean; error?: string } => {
    const allowedTypes = [
      'image/jpeg',
      'image/jpg',
      'image/png',
      'image/gif',
      'video/mp4',
      'video/mov',
      'video/avi',
      'video/webm',
    ]

    if (!allowedTypes.includes(file.type)) {
      return {
        valid: false,
        error:
          'サポートされているファイル形式: JPEG, PNG, GIF, MP4, MOV, AVI, WebM',
      }
    }

    return { valid: true }
  }

  return {
    getRecipeMedia,
    getMediaDetail,
    uploadMedia,
    updateMedia,
    deleteMedia,
    approveMedia,
    unapproveMedia,
    getPendingMedia,
    getMediaTypeFromFile,
    validateFileSize,
    validateFileType,
  }
}
