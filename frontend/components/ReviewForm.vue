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
          <div class="rating-container">
            <!-- 押しやすい星評価ボタン（整数のみ） -->
            <div class="star-buttons">
              <button
                v-for="i in 5"
                :key="i"
                type="button"
                class="star-button"
                :class="{ active: form.rating >= i }"
                @click="setRating(i)"
                @mouseenter="hoverRating = i"
                @mouseleave="hoverRating = 0"
              >
                <v-icon
                  :color="getStarButtonColor(i)"
                  size="36"
                  class="star-icon"
                >
                  {{
                    form.rating >= i || hoverRating >= i
                      ? 'mdi-star'
                      : 'mdi-star-outline'
                  }}
                </v-icon>
              </button>
            </div>
            <div class="rating-display">
              <span class="rating-value">{{ form.rating || 0 }}</span>
              <span class="rating-text">{{ getRatingText(form.rating) }}</span>
            </div>
          </div>
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
import { computed, ref, watch } from '#imports'
import type { Ref } from '#imports'
import type { CreateReviewRequest, RecipeReview } from '~/types/review'
import { useReviewApi } from '~/composables/useReviewApi'

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

// フォームデータ
const form = ref<CreateReviewRequest>({
  rating: 0,
  comment: '',
  recipeId: props.recipeId,
  taste_score: 0,
  difficulty_score: 0,
  instruction_clarity: 0,
  review_images: [],
})

// formDataエイリアス（テンプレートとの互換性）
const formData = form

// ホバー状態の管理
const hoverRating = ref(0)

// フォームバリデーション状態
const isEditing = computed(() => !!props.existingReview)

// 画像URL入力用
const imageUrl = ref('')

// 送信状態
const isSubmitting = ref(false)

// フォーム参照
const formRef = ref()

// フォーム有効性
const isFormValid = ref(false)

// 既存レビューがある場合、フォームに設定
watch(
  () => props.existingReview,
  (review) => {
    if (review) {
      form.value = {
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
  if (rating === 0) return '評価なし'
  if (rating <= 1) return '悪い'
  if (rating <= 2) return 'いまいち'
  if (rating <= 3) return '普通'
  if (rating <= 4) return '良い'
  return '最高'
}

// 評価を設定（整数のみ）
const setRating = (rating: number) => {
  form.value.rating = rating
}

// 星ボタンの色を取得
const getStarButtonColor = (starIndex: number): string => {
  const currentRating = hoverRating.value || form.value.rating
  return currentRating >= starIndex ? '#FFD700' : '#E0E0E0'
}

// 画像URL追加
const addImageUrl = () => {
  if (imageUrl.value.trim()) {
    if (!form.value.review_images) {
      form.value.review_images = []
    }
    if (form.value.review_images.length < 5) {
      form.value.review_images.push(imageUrl.value.trim())
      imageUrl.value = ''
    }
  }
}

// 画像削除
const removeImage = (index: number) => {
  if (form.value.review_images) {
    form.value.review_images.splice(index, 1)
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
        form.value
      )
    } else {
      review = await createReview(props.recipeId, form.value)
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

.rating-section {
  margin-bottom: 24px;
}

.rating-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.star-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.star-button {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.star-button:hover {
  background-color: rgba(255, 215, 0, 0.1);
  transform: scale(1.1);
}

.star-button:active {
  transform: scale(0.95);
}

.star-icon {
  transition: all 0.2s ease;
}

.stars-display {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stars-container {
  display: flex;
  gap: 4px;
}

.rating-display {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.rating-value {
  font-size: 24px;
  font-weight: 700;
  color: #ffd700;
}

.rating-text {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.additional-ratings {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

@media (min-width: 768px) {
  .additional-ratings {
    grid-template-columns: repeat(3, 1fr);
  }
}

.rating-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.rating-item .v-label {
  font-size: 14px;
  font-weight: 600;
  color: #555;
}

.comment-section {
  margin-bottom: 24px;
}

.images-section {
  margin-bottom: 24px;
}

.image-input-container {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.image-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 12px;
}

.image-preview-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid #e0e0e0;
}

.image-preview-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-remove-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

@media (max-width: 600px) {
  .rating-container {
    gap: 12px;
  }

  .stars-display {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions .v-btn {
    width: 100%;
  }
}
</style>
