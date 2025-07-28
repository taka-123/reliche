<template>
  <v-card class="media-detail-dialog">
    <v-card-title class="d-flex align-center justify-between pa-4">
      <div class="d-flex align-center">
        <v-icon
          :icon="media.media_type === 'image' ? 'mdi-image' : 'mdi-video'"
          class="me-2"
        />
        <span>メディア詳細</span>
      </div>

      <div class="d-flex align-center gap-2">
        <!-- 承認ステータス -->
        <v-chip
          :color="media.is_approved ? 'success' : 'warning'"
          size="small"
          variant="outlined"
        >
          <v-icon
            :icon="media.is_approved ? 'mdi-check-circle' : 'mdi-clock'"
            class="me-1"
            size="16"
          />
          {{ media.is_approved ? '承認済み' : '承認待ち' }}
        </v-chip>

        <v-btn
          icon="mdi-close"
          variant="text"
          size="small"
          @click="$emit('close')"
        />
      </div>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-0">
      <!-- メディアコンテンツ -->
      <div class="media-container">
        <img
          v-if="media.media_type === 'image'"
          :src="media.full_url"
          :alt="media.description || 'レシピ画像'"
          class="media-content"
        />
        <video v-else :src="media.full_url" controls class="media-content" />
      </div>

      <!-- メディア情報 -->
      <div class="pa-4">
        <!-- 基本情報 -->
        <div class="mb-4">
          <h3 class="text-h6 mb-2">基本情報</h3>
          <v-row dense>
            <v-col cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">投稿者</span>
                <p class="text-body-1">{{ media.user.name }}</p>
              </div>
            </v-col>
            <v-col cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">投稿日時</span>
                <p class="text-body-1">
                  {{ formatDateTime(media.created_at) }}
                </p>
              </div>
            </v-col>
            <v-col cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">ファイルサイズ</span>
                <p class="text-body-1">{{ media.human_readable_file_size }}</p>
              </div>
            </v-col>
            <v-col cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">ファイル形式</span>
                <p class="text-body-1">{{ media.mime_type }}</p>
              </div>
            </v-col>
          </v-row>
        </div>

        <!-- メタデータ -->
        <div v-if="hasMetadata" class="mb-4">
          <h3 class="text-h6 mb-2">メタデータ</h3>
          <v-row dense>
            <v-col
              v-if="media.metadata.width && media.metadata.height"
              cols="12"
              sm="6"
            >
              <div class="info-item">
                <span class="text-caption text-grey">解像度</span>
                <p class="text-body-1">
                  {{ media.metadata.width }} × {{ media.metadata.height }}
                </p>
              </div>
            </v-col>
            <v-col v-if="media.metadata.orientation" cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">向き</span>
                <p class="text-body-1">
                  {{
                    media.metadata.orientation === 'landscape'
                      ? '横向き'
                      : '縦向き'
                  }}
                </p>
              </div>
            </v-col>
            <v-col v-if="media.metadata.duration" cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">再生時間</span>
                <p class="text-body-1">{{ media.metadata.duration }}秒</p>
              </div>
            </v-col>
            <v-col v-if="media.metadata.resolution" cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">動画解像度</span>
                <p class="text-body-1">{{ media.metadata.resolution }}</p>
              </div>
            </v-col>
          </v-row>
        </div>

        <!-- 説明文 -->
        <div v-if="media.description" class="mb-4">
          <h3 class="text-h6 mb-2">説明・キャプション</h3>
          <v-card variant="outlined" class="pa-3">
            <p class="text-body-1 mb-0">{{ media.description }}</p>
          </v-card>
        </div>

        <!-- 承認情報 -->
        <div v-if="media.is_approved && media.approver" class="mb-4">
          <h3 class="text-h6 mb-2">承認情報</h3>
          <v-row dense>
            <v-col cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">承認者</span>
                <p class="text-body-1">{{ media.approver.name }}</p>
              </div>
            </v-col>
            <v-col cols="12" sm="6">
              <div class="info-item">
                <span class="text-caption text-grey">承認日時</span>
                <p class="text-body-1">
                  {{ formatDateTime(media.approved_at!) }}
                </p>
              </div>
            </v-col>
          </v-row>
        </div>
      </div>
    </v-card-text>

    <v-divider />

    <!-- アクションボタン -->
    <v-card-actions class="pa-4">
      <v-spacer />

      <!-- ダウンロード -->
      <v-btn variant="outlined" color="primary" @click="downloadMedia">
        <v-icon icon="mdi-download" class="me-1" />
        ダウンロード
      </v-btn>

      <!-- 編集 -->
      <v-btn
        v-if="canEdit"
        variant="outlined"
        color="primary"
        @click="$emit('edit', media)"
      >
        <v-icon icon="mdi-pencil" class="me-1" />
        編集
      </v-btn>

      <!-- 削除 -->
      <v-btn
        v-if="canDelete"
        variant="outlined"
        color="error"
        @click="$emit('delete', media)"
      >
        <v-icon icon="mdi-delete" class="me-1" />
        削除
      </v-btn>

      <!-- 閉じる -->
      <v-btn variant="outlined" @click="$emit('close')"> 閉じる </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import type { RecipeMedia } from '~/types/media'

interface Props {
  media: RecipeMedia
}

interface Emits {
  (e: 'close'): void
  (e: 'edit', media: RecipeMedia): void
  (e: 'delete', media: RecipeMedia): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { user } = useAuth()

// 権限チェック
const canEdit = computed(() => user.value?.id === props.media.user_id)
const canDelete = computed(() => user.value?.id === props.media.user_id)

// メタデータの存在チェック
const hasMetadata = computed(() => {
  const metadata = props.media.metadata
  return (
    metadata &&
    (metadata.width ||
      metadata.height ||
      metadata.orientation ||
      metadata.duration ||
      metadata.resolution)
  )
})

// 日時フォーマット
const formatDateTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// メディアダウンロード
const downloadMedia = () => {
  const link = document.createElement('a')
  link.href = props.media.full_url
  link.download = props.media.original_filename
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<style scoped>
.media-detail-dialog {
  max-height: 90vh;
  overflow-y: auto;
}

.media-container {
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #f5f5f5;
  min-height: 300px;
  max-height: 60vh;
}

.media-content {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.info-item {
  margin-bottom: 8px;
}

.info-item .text-caption {
  display: block;
  margin-bottom: 2px;
}

.info-item .text-body-1 {
  margin: 0;
  word-break: break-word;
}

.gap-2 {
  gap: 8px;
}
</style>
