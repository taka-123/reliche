<template>
  <div class="media-gallery">
    <!-- ヘッダー -->
    <div class="d-flex justify-between align-center mb-4">
      <div class="d-flex align-center">
        <v-icon icon="mdi-image-multiple" class="me-2" />
        <h3 class="text-h6">レシピメディア</h3>
        <v-chip v-if="totalCount > 0" class="ms-2" size="small" color="primary">
          {{ totalCount }}
        </v-chip>
      </div>

      <v-btn
        v-if="canUpload"
        color="primary"
        variant="outlined"
        size="small"
        @click="showUploadDialog = true"
      >
        <v-icon icon="mdi-plus" class="me-1" />
        アップロード
      </v-btn>
    </div>

    <!-- 読み込み中 -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular indeterminate color="primary" />
      <p class="text-body-2 mt-2">メディアを読み込み中...</p>
    </div>

    <!-- メディアなし -->
    <v-card
      v-else-if="mediaList.length === 0"
      variant="outlined"
      class="text-center py-8"
    >
      <v-icon icon="mdi-image-off" size="48" color="grey" class="mb-2" />
      <p class="text-body-1 text-grey">まだメディアがありません</p>
      <p class="text-body-2 text-grey-darken-1">
        料理の写真や動画をアップロードして、レシピをより魅力的にしましょう
      </p>
      <v-btn
        v-if="canUpload"
        color="primary"
        class="mt-3"
        @click="showUploadDialog = true"
      >
        <v-icon icon="mdi-camera-plus" class="me-1" />
        最初のメディアをアップロード
      </v-btn>
    </v-card>

    <!-- メディアグリッド -->
    <div v-else class="media-grid">
      <div v-for="media in mediaList" :key="media.id" class="media-item">
        <v-card class="media-card" @click="openMediaDetail(media)">
          <!-- メディアコンテンツ -->
          <div class="media-content">
            <img
              v-if="media.media_type === 'image'"
              :src="media.full_url"
              :alt="media.description || 'レシピ画像'"
              class="media-image"
            />
            <div v-else class="video-thumbnail">
              <video
                :src="media.full_url"
                class="media-video"
                muted
                @mouseenter="playPreview"
                @mouseleave="pausePreview"
              />
              <div class="video-overlay">
                <v-icon icon="mdi-play-circle" size="48" color="white" />
              </div>
            </div>
          </div>

          <!-- メディア情報 -->
          <v-card-text class="pa-2">
            <div class="d-flex align-center justify-between">
              <div class="media-info">
                <p class="text-caption text-grey-darken-1 mb-1">
                  {{ media.user.name }}
                </p>
                <p class="text-caption text-grey">
                  {{ formatDate(media.created_at) }}
                </p>
              </div>

              <div class="media-actions">
                <v-menu offset-y>
                  <template #activator="{ props: menuProps }">
                    <v-btn
                      icon="mdi-dots-vertical"
                      size="small"
                      variant="text"
                      v-bind="menuProps"
                      @click.stop
                    />
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      prepend-icon="mdi-eye"
                      title="詳細表示"
                      @click="openMediaDetail(media)"
                    />
                    <v-list-item
                      v-if="canEdit(media)"
                      prepend-icon="mdi-pencil"
                      title="編集"
                      @click="editMedia(media)"
                    />
                    <v-list-item
                      v-if="canDelete(media)"
                      prepend-icon="mdi-delete"
                      title="削除"
                      class="text-error"
                      @click="deleteMedia(media)"
                    />
                  </v-list>
                </v-menu>
              </div>
            </div>

            <p
              v-if="media.description"
              class="text-body-2 mt-2 media-description"
            >
              {{ media.description }}
            </p>
          </v-card-text>
        </v-card>
      </div>
    </div>

    <!-- ページネーション -->
    <div v-if="totalPages > 1" class="d-flex justify-center mt-4">
      <v-pagination
        v-model="currentPage"
        :length="totalPages"
        :total-visible="5"
        @update:model-value="loadMedia"
      />
    </div>

    <!-- アップロードダイアログ -->
    <v-dialog v-model="showUploadDialog" max-width="600px" persistent>
      <MediaUploadForm
        :recipe-id="recipeId"
        @success="onUploadSuccess"
        @cancel="showUploadDialog = false"
      />
    </v-dialog>

    <!-- 編集ダイアログ -->
    <v-dialog v-model="showEditDialog" max-width="600px" persistent>
      <MediaUploadForm
        v-if="editingMedia"
        :recipe-id="recipeId"
        :existing-media="editingMedia"
        @success="onEditSuccess"
        @cancel="showEditDialog = false"
      />
    </v-dialog>

    <!-- 詳細ダイアログ -->
    <v-dialog v-model="showDetailDialog" max-width="800px">
      <MediaDetailDialog
        v-if="detailMedia"
        :media="detailMedia"
        @close="showDetailDialog = false"
        @edit="editMediaFromDetail"
        @delete="deleteMediaFromDetail"
      />
    </v-dialog>

    <!-- 削除確認ダイアログ -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h6">メディア削除</v-card-title>
        <v-card-text>
          このメディアを削除してもよろしいですか？
          <br />
          この操作は取り消せません。
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="outlined" @click="showDeleteDialog = false">
            キャンセル
          </v-btn>
          <v-btn color="error" :loading="deleting" @click="confirmDelete">
            削除
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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
import type { RecipeMedia } from '~/types/media'

interface Props {
  recipeId: number
}

const props = defineProps<Props>()

const { getRecipeMedia, deleteMedia: deleteMediaApi } = useMediaApi()
const { user } = useAuth()

// リアクティブな状態
const loading = ref(false)
const deleting = ref(false)
const mediaList = ref<RecipeMedia[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const totalCount = ref(0)

// ダイアログ状態
const showUploadDialog = ref(false)
const showEditDialog = ref(false)
const showDetailDialog = ref(false)
const showDeleteDialog = ref(false)

// 選択されたメディア
const editingMedia = ref<RecipeMedia | null>(null)
const detailMedia = ref<RecipeMedia | null>(null)
const deletingMedia = ref<RecipeMedia | null>(null)

// メッセージ状態
const showSuccess = ref(false)
const successMessage = ref('')
const showError = ref(false)
const errorMessage = ref('')

// 権限チェック
const canUpload = computed(() => !!user.value)
const canEdit = (media: RecipeMedia) => user.value?.id === media.user_id
const canDelete = (media: RecipeMedia) => user.value?.id === media.user_id

// 初期読み込み
onMounted(() => {
  loadMedia()
})

// メディア一覧読み込み
const loadMedia = async (page: number = currentPage.value) => {
  loading.value = true

  try {
    const response = await getRecipeMedia(props.recipeId, page)
    mediaList.value = response.data
    currentPage.value = response.meta.current_page
    totalPages.value = response.meta.last_page
    totalCount.value = response.meta.total
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Failed to load media:', error)
    errorMessage.value = 'メディアの読み込みに失敗しました'
    showError.value = true
  } finally {
    loading.value = false
  }
}

// 日付フォーマット
const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// メディア詳細表示
const openMediaDetail = (media: RecipeMedia) => {
  detailMedia.value = media
  showDetailDialog.value = true
}

// メディア編集
const editMedia = (media: RecipeMedia) => {
  editingMedia.value = media
  showEditDialog.value = true
}

// 詳細画面からの編集
const editMediaFromDetail = (media: RecipeMedia) => {
  showDetailDialog.value = false
  editMedia(media)
}

// メディア削除
const deleteMedia = (media: RecipeMedia) => {
  deletingMedia.value = media
  showDeleteDialog.value = true
}

// 詳細画面からの削除
const deleteMediaFromDetail = (media: RecipeMedia) => {
  showDetailDialog.value = false
  deleteMedia(media)
}

// 削除確認
const confirmDelete = async () => {
  if (!deletingMedia.value) return

  deleting.value = true

  try {
    await deleteMediaApi(props.recipeId, deletingMedia.value.id)

    successMessage.value = 'メディアを削除しました'
    showSuccess.value = true
    showDeleteDialog.value = false

    // リストを再読み込み
    await loadMedia()
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Failed to delete media:', error)
    errorMessage.value = 'メディアの削除に失敗しました'
    showError.value = true
  } finally {
    deleting.value = false
    deletingMedia.value = null
  }
}

// アップロード成功
const onUploadSuccess = (media: RecipeMedia) => {
  showUploadDialog.value = false
  loadMedia(1) // 最初のページに戻る
}

// 編集成功
const onEditSuccess = (media: RecipeMedia) => {
  showEditDialog.value = false
  editingMedia.value = null

  // リストの該当アイテムを更新
  const index = mediaList.value.findIndex((m) => m.id === media.id)
  if (index !== -1) {
    mediaList.value[index] = media
  }
}

// 動画プレビュー制御
const playPreview = (event: Event) => {
  const video = event.target as HTMLVideoElement
  video.play()
}

const pausePreview = (event: Event) => {
  const video = event.target as HTMLVideoElement
  video.pause()
  video.currentTime = 0
}
</script>

<style scoped>
.media-gallery {
  width: 100%;
}

.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.media-card {
  cursor: pointer;
  transition:
    transform 0.2s ease-in-out,
    box-shadow 0.2s ease-in-out;
}

.media-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.media-content {
  position: relative;
  aspect-ratio: 4/3;
  overflow: hidden;
}

.media-image,
.media-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-thumbnail {
  position: relative;
  width: 100%;
  height: 100%;
}

.video-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.media-info {
  flex: 1;
  min-width: 0;
}

.media-description {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.media-actions {
  flex-shrink: 0;
}
</style>
