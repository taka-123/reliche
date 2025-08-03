<template>
  <v-card class="review-form-card">
    <v-card-title class="form-title">
      <v-icon
        color="primary"
        class="mr-2"
      >
        mdi-star-outline
      </v-icon>
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
          <v-label class="rating-label">
            総合評価 *
          </v-label>
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
              <v-icon class="mr-2">
                mdi-chart-line
              </v-icon>
              詳細評価（任意）
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <!-- 味評価 -->
              <div class="detail-rating-item">
                <v-label class="detail-rating-label">
                  味
                </v-label>
                <v-rating
                  v-model="form.taste_score"
                  color="orange"
                  active-color="orange"
                  size="small"
                  hover
                />
              </div>

              <!-- 難易度評価 -->
              <div class="detail-rating-item">
                <v-label class="detail-rating-label">
                  難易度
                </v-label>
                <v-rating
                  v-model="form.difficulty_score"
                  color="blue"
                  active-color="blue"
                  size="small"
                  hover
                />
                <span class="difficulty-hint">（簡単 1 ←→ 5 難しい）</span>
              </div>

              <!-- 手順明確性評価 -->
              <div class="detail-rating-item">
                <v-label class="detail-rating-label">
                  手順の分かりやすさ
                </v-label>
                <v-rating
                  v-model="form.instruction_clarity"
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
          v-model="form.comment"
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
          <template #append>
            <v-btn
              icon="mdi-plus"
              size="small"
              variant="text"
              @click="addImageUrl"
            />
          </template>
        </v-text-field>

        <!-- 追加された画像一覧 -->
        <div
          v-if="form.review_images && form.review_images.length > 0"
          class="image-list"
        >
          <v-chip
            v-for="(url, index) in form.review_images"
            :key="index"
            closable
            color="primary"
            variant="outlined"
            class="image-chip"
            @click:close="removeImage(index)"
          >
            <v-icon start>
              mdi-image
            </v-icon>
            画像 {{ index + 1 }}
          </v-chip>
        </div>
      </v-form>
    </v-card-text>

    <v-card-actions class="form-actions">
      <v-btn
        variant="outlined"
        @click="cancel"
      >
        キャンセル
      </v-btn>
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
// Nuxt 3のauto-importを使用
// Nuxt 3のauto-importを使用（ref, computed, watchは自動インポート）
import { ref, computed, watch } from '#imports'
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

// ホバー評価設定
const hoverRating = ref(0)

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
        recipeId: props.recipeId,
      }
    }
  },
  { immediate: true },
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
  const texts = ['', 'とても悪い', '悪い', '普通', '良い', 'とても良い']
  return texts[rating] || ''
}

// 評価を設定（整数のみ）
const setRating = (rating: number) => {
  form.value.rating = rating
}

// 星ボタンの色を取得
const getStarButtonColor = (starIndex: number): string => {
  return form.value.rating >= starIndex || hoverRating.value >= starIndex
    ? 'orange'
    : 'grey'
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

// キャンセル
const cancel = () => {
  emit('cancel')
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
        form.value,
      )
    }
    else {
      review = await createReview(props.recipeId, form.value)
    }

    emit('success', review)
  }
  catch (error: unknown) {
    const errorMessage
      = error instanceof Error ? error.message : 'レビューの投稿に失敗しました'
    emit('error', errorMessage)
  }
  finally {
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
  background-color: rgb(255 215 0 / 10%);
  transform: scale(1.1);
}

.star-button:active {
  transform: scale(0.95);
}

.star-icon {
  transition: all 0.2s ease;
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

.detail-ratings {
  margin-bottom: 24px;
}

.detail-rating-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.detail-rating-label {
  font-size: 14px;
  font-weight: 600;
  color: #555;
}

.difficulty-hint {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

.comment-input {
  margin-bottom: 24px;
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
  margin: 2px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

@media (width <= 600px) {
  .rating-container {
    gap: 12px;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions .v-btn {
    width: 100%;
  }
}
</style>
