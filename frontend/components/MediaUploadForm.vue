<template>
  <div class="media-upload-form">
    <v-card flat>
      <v-card-title class="text-h6 pb-2">
        <v-icon icon="mdi-camera-plus" class="me-2" />
        {{ isEditing ? 'メディア編集' : 'メディアアップロード' }}
      </v-card-title>

      <v-card-text>
        <v-form @submit.prevent="handleSubmit">
          <!-- ファイル選択（編集時は非表示） -->
          <div v-if="!isEditing" class="mb-4">
            <v-file-input
              v-model="selectedFile"
              :loading="uploading"
              :disabled="uploading"
              label="画像または動画ファイルを選択"
              prepend-icon="mdi-camera"
              accept="image/*,video/*"
              show-size
              @change="onFileSelected"
            />
            <v-alert
              v-if="fileError"
              type="error"
              density="compact"
              class="mt-2"
            >
              {{ fileError }}
            </v-alert>
          </div>

          <!-- プレビュー -->
          <div v-if="previewUrl" class="mb-4">
            <v-card variant="outlined" class="pa-2">
              <div class="d-flex align-center mb-2">
                <v-icon
                  :icon="previewType === 'image' ? 'mdi-image' : 'mdi-video'"
                  class="me-2"
                />
                <span class="text-body-2">プレビュー</span>
              </div>

              <div class="preview-container">
                <img
                  v-if="previewType === 'image'"
                  :src="previewUrl"
                  alt="プレビュー"
                  class="preview-image"
                />
                <video
                  v-else-if="previewType === 'video'"
                  :src="previewUrl"
                  controls
                  class="preview-video"
                />
              </div>
            </v-card>
          </div>

          <!-- 説明文 -->
          <v-textarea
            v-model="description"
            label="説明・キャプション（任意）"
            rows="3"
            counter="1000"
            :disabled="uploading"
            placeholder="この画像・動画について説明を入力してください..."
          />

          <!-- アクション -->
          <div class="d-flex justify-end gap-2 mt-4">
            <v-btn
              variant="outlined"
              color="grey"
              :disabled="uploading"
              @click="$emit('cancel')"
            >
              キャンセル
            </v-btn>
            <v-btn
              type="submit"
              color="primary"
              :loading="uploading"
              :disabled="!canSubmit"
            >
              {{ isEditing ? '更新する' : 'アップロード' }}
            </v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>

    <!-- 成功メッセージ -->
    <v-snackbar v-model="showSuccess" color="success" timeout="3000">
      {{ successMessage }}
    </v-snackbar>

    <!-- エラーメッセージ -->
    <v-snackbar v-model="showError" color="error" timeout="5000">
      {{ errorMessage }}
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import type {
  RecipeMedia,
  MediaUploadRequest,
  MediaUpdateRequest,
} from '~/types/media'

interface Props {
  recipeId: number
  existingMedia?: RecipeMedia
}

interface Emits {
  (e: 'success', media: RecipeMedia): void
  (e: 'cancel'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const {
  uploadMedia,
  updateMedia,
  validateFileSize,
  validateFileType,
  getMediaTypeFromFile,
} = useMediaApi()

// リアクティブな状態
const selectedFile = ref<File[]>([])
const description = ref('')
const uploading = ref(false)
const fileError = ref('')
const previewUrl = ref('')
const previewType = ref<'image' | 'video' | null>(null)

// 成功・エラーメッセージ
const showSuccess = ref(false)
const successMessage = ref('')
const showError = ref(false)
const errorMessage = ref('')

// 編集モードかどうか
const isEditing = computed(() => !!props.existingMedia)

// 送信可能かどうか
const canSubmit = computed(() => {
  if (isEditing.value) {
    return !uploading.value
  }
  return selectedFile.value.length > 0 && !fileError.value && !uploading.value
})

// 既存メディアの場合は初期値を設定
watch(
  () => props.existingMedia,
  (media) => {
    if (media) {
      description.value = media.description || ''
      previewUrl.value = media.full_url
      previewType.value = media.media_type
    }
  },
  { immediate: true }
)

// ファイル選択時の処理
const onFileSelected = () => {
  fileError.value = ''
  previewUrl.value = ''
  previewType.value = null

  if (selectedFile.value.length === 0) {
    return
  }

  const file = selectedFile.value[0]

  // ファイル形式バリデーション
  const typeValidation = validateFileType(file)
  if (!typeValidation.valid) {
    fileError.value = typeValidation.error || 'ファイル形式エラー'
    return
  }

  // ファイルサイズバリデーション
  const sizeValidation = validateFileSize(file)
  if (!sizeValidation.valid) {
    fileError.value = sizeValidation.error || 'ファイルサイズエラー'
    return
  }

  // プレビュー作成
  const mediaType = getMediaTypeFromFile(file)
  if (mediaType !== 'unknown') {
    previewType.value = mediaType
    previewUrl.value = URL.createObjectURL(file)
  }
}

// フォーム送信処理
const handleSubmit = async () => {
  if (!canSubmit.value) return

  uploading.value = true

  try {
    if (isEditing.value && props.existingMedia) {
      // 更新処理
      const updateData: MediaUpdateRequest = {
        description: description.value || undefined,
      }

      const response = await updateMedia(
        props.recipeId,
        props.existingMedia.id,
        updateData
      )

      successMessage.value = 'メディア情報を更新しました'
      showSuccess.value = true
      emit('success', response.data)
    } else {
      // アップロード処理
      const file = selectedFile.value[0]
      const uploadData: MediaUploadRequest = {
        file,
        description: description.value || undefined,
      }

      const response = await uploadMedia(props.recipeId, uploadData)

      successMessage.value = response.message
      showSuccess.value = true
      emit('success', response.data)
    }
  } catch (error: unknown) {
    // eslint-disable-next-line no-console
    console.error('Media operation failed:', error)

    const errorData = error as {
      data?: { error?: string; details?: Record<string, string[]> }
    }
    if (errorData.data?.error) {
      errorMessage.value = errorData.data.error
    } else if (errorData.data?.details) {
      // バリデーションエラーの場合
      const details = Object.values(errorData.data.details).flat() as string[]
      errorMessage.value = details.join(', ')
    } else {
      errorMessage.value = isEditing.value
        ? 'メディア情報の更新に失敗しました'
        : 'メディアのアップロードに失敗しました'
    }

    showError.value = true
  } finally {
    uploading.value = false
  }
}

// コンポーネント破棄時にオブジェクトURLをクリーンアップ
onUnmounted(() => {
  if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(previewUrl.value)
  }
})
</script>

<style scoped>
.media-upload-form {
  max-width: 600px;
}

.preview-container {
  display: flex;
  justify-content: center;
  border-radius: 8px;
  overflow: hidden;
  background-color: #f5f5f5;
}

.preview-image {
  max-width: 100%;
  max-height: 300px;
  object-fit: contain;
}

.preview-video {
  max-width: 100%;
  max-height: 300px;
}

.gap-2 {
  gap: 8px;
}
</style>
