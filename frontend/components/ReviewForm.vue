<template>
  <v-card class="review-form-card">
    <v-card-title class="form-title">
      <v-icon color="primary" class="mr-2">mdi-star-outline</v-icon>
      {{ isEditing ? 'レビューを編集' : 'レビューを投稿' }}
    </v-card-title>

    <v-card-text>
      <v-form
        ref="formRef"
        v-model="isFormValid"
        @submit.prevent="submitReview"
      >
        <!-- 総合評価 -->
        <div class="rating-section">
          <v-label class="rating-label">総合評価 *</v-label>
          <v-rating
            v-model="formData.rating"
            :rules="[rules.rating]"
            color="primary"
            active-color="primary"
            size="large"
            hover
            half-increments
            class="rating-input"
          />
          <span class="rating-text">{{ getRatingText(formData.rating) }}</span>
        </div>

        <!-- 詳細評価 -->
        <v-expansion-panels class="detail-ratings">
          <v-expansion-panel>
            <v-expansion-panel-title>
              <v-icon class="mr-2">mdi-chart-line</v-icon>
              詳細評価（任意）
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <!-- 味評価 -->
              <div class="detail-rating-item">
                <v-label class="detail-rating-label">味</v-label>
                <v-rating
                  v-model="formData.taste_score"
                  color="orange"
                  active-color="orange"
                  size="small"
                  hover
                />
              </div>

              <!-- 難易度評価 -->
              <div class="detail-rating-item">
                <v-label class="detail-rating-label">難易度</v-label>
                <v-rating
                  v-model="formData.difficulty_score"
                  color="blue"
                  active-color="blue"
                  size="small"
                  hover
                />
                <span class="difficulty-hint">（簡単 1 ←→ 5 難しい）</span>
              </div>

              <!-- 手順明確性評価 -->
              <div class="detail-rating-item">
                <v-label class="detail-rating-label"
                  >手順の分かりやすさ</v-label
                >
                <v-rating
                  v-model="formData.instruction_clarity"
                  color="green"
                  active-color="green"
                  size="small"
                  hover
                />
              </div>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <!-- コメント -->
        <v-textarea
          v-model="formData.comment"
          label="コメント（任意）"
          placeholder="このレシピの感想を教えてください..."
          :rules="[rules.comment]"
          rows="4"
          counter="1000"
          variant="outlined"
          class="comment-input"
        />

        <!-- 画像URL（将来の拡張用） -->
        <v-text-field
          v-model="imageUrl"
          label="画像URL（任意）"
          placeholder="https://example.com/image.jpg"
          variant="outlined"
          class="image-input"
          @blur="addImageUrl"
        >
          <template #append-inner>
            <v-btn
              v-if="imageUrl"
              icon
              size="small"
              variant="text"
              @click="addImageUrl"
            >
              <v-icon>mdi-plus</v-icon>
            </v-btn>
          </template>
        </v-text-field>

        <!-- 追加された画像一覧 -->
        <div
          v-if="formData.review_images && formData.review_images.length > 0"
          class="image-list"
        >
          <v-chip
            v-for="(url, index) in formData.review_images"
            :key="index"
            closable
            color="primary"
            variant="outlined"
            class="image-chip"
            @click:close="removeImage(index)"
          >
            <v-icon start>mdi-image</v-icon>
            画像 {{ index + 1 }}
          </v-chip>
        </div>
      </v-form>
    </v-card-text>

    <v-card-actions class="form-actions">
      <v-btn variant="outlined" @click="$emit('cancel')"> キャンセル </v-btn>
      <v-spacer />
      <v-btn
        :disabled="!isFormValid || isSubmitting"
        :loading="isSubmitting"
        color="primary"
        @click="submitReview"
      >
        {{ isEditing ? '更新する' : '投稿する' }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { CreateReviewRequest, RecipeReview } from '~/types/review'

interface Props {
  recipeId: string | number
  existingReview?: RecipeReview
}

interface Emits {
  (e: 'success', review: RecipeReview): void
  (e: 'cancel'): void
  (e: 'error', error: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const { createReview, updateReview } = useReviewApi()

const formRef = ref()
const isFormValid = ref(false)
const isSubmitting = ref(false)
const imageUrl = ref('')

const isEditing = computed(() => !!props.existingReview)

// フォームデータ
const formData = ref<CreateReviewRequest>({
  rating: 0,
  taste_score: undefined,
  difficulty_score: undefined,
  instruction_clarity: undefined,
  comment: '',
  review_images: [],
})

// 既存レビューがある場合、フォームに設定
watch(
  () => props.existingReview,
  (review) => {
    if (review) {
      formData.value = {
        rating: review.rating,
        taste_score: review.taste_score,
        difficulty_score: review.difficulty_score,
        instruction_clarity: review.instruction_clarity,
        comment: review.comment || '',
        review_images: review.review_images || [],
      }
    }
  },
  { immediate: true }
)

// バリデーションルール
const rules = {
  rating: (value: number) => {
    if (!value || value < 1) return '評価を選択してください'
    return true
  },
  comment: (value: string) => {
    if (value && value.length > 1000)
      return 'コメントは1000文字以内で入力してください'
    return true
  },
}

// 評価テキスト
const getRatingText = (rating: number): string => {
  if (rating === 0) return ''
  if (rating <= 1) return '改善が必要'
  if (rating <= 2) return 'イマイチ'
  if (rating <= 3) return '普通'
  if (rating <= 4) return '良い'
  return '最高！'
}

// 画像URL追加
const addImageUrl = () => {
  if (imageUrl.value.trim()) {
    if (!formData.value.review_images) {
      formData.value.review_images = []
    }
    if (formData.value.review_images.length < 5) {
      formData.value.review_images.push(imageUrl.value.trim())
      imageUrl.value = ''
    }
  }
}

// 画像削除
const removeImage = (index: number) => {
  if (formData.value.review_images) {
    formData.value.review_images.splice(index, 1)
  }
}

// レビュー投稿/更新
const submitReview = async () => {
  if (!isFormValid.value) return

  isSubmitting.value = true

  try {
    let review: RecipeReview

    if (isEditing.value && props.existingReview) {
      review = await updateReview(
        props.recipeId,
        props.existingReview.id,
        formData.value
      )
    } else {
      review = await createReview(props.recipeId, formData.value)
    }

    emit('success', review)
  } catch (error: unknown) {
    const errorMessage =
      error instanceof Error ? error.message : 'レビューの投稿に失敗しました'
    emit('error', errorMessage)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.review-form-card {
  max-width: 600px;
  margin: 0 auto;
}

.form-title {
  background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
  color: white;
  padding: 16px 24px;
}

.rating-section {
  margin-bottom: 24px;
  text-align: center;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.rating-label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

.rating-input {
  justify-content: center;
  margin-bottom: 8px;
}

.rating-text {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.detail-ratings {
  margin-bottom: 24px;
}

.detail-rating-item {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
  padding: 8px 0;
}

.detail-rating-label {
  min-width: 120px;
  font-weight: 500;
  color: #333;
}

.difficulty-hint {
  font-size: 12px;
  color: #999;
  margin-left: 8px;
}

.comment-input {
  margin-bottom: 16px;
}

.image-input {
  margin-bottom: 16px;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.image-chip {
  margin-bottom: 4px;
}

.form-actions {
  padding: 16px 24px;
  background: #fafafa;
}

/* レスポンシブ対応 */
@media (max-width: 600px) {
  .detail-rating-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .detail-rating-label {
    min-width: auto;
  }
}
</style>
