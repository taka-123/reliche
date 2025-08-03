<template>
  <div class="review-list">
    <!-- 統計情報 -->
    <div
      v-if="statistics"
      class="statistics-section"
    >
      <v-card class="statistics-card">
        <v-card-title class="statistics-title">
          <v-icon
            color="primary"
            class="mr-2"
          >
            mdi-chart-bar
          </v-icon>
          評価統計
        </v-card-title>
        <v-card-text>
          <div class="overall-rating">
            <div class="rating-display">
              <span class="rating-number">{{
                statistics?.average_rating?.toFixed(1) || '0.0'
              }}</span>
              <v-rating
                :model-value="statistics?.average_rating || 0"
                color="primary"
                active-color="primary"
                size="small"
                readonly
                half-increments
                density="compact"
              />
              <span class="review-count">（{{ statistics?.total_reviews || 0 }}件のレビュー）</span>
            </div>
          </div>

          <!-- 評価分布 -->
          <div class="rating-distribution">
            <div
              v-for="star in [5, 4, 3, 2, 1]"
              :key="star"
              class="distribution-row"
            >
              <span class="star-label">{{ star }}★</span>
              <v-progress-linear
                :model-value="
                  statistics.rating_distribution[star]?.percentage || 0
                "
                color="primary"
                height="8"
                rounded
                class="distribution-bar"
              />
              <span class="percentage">{{
                statistics.rating_distribution[star]?.percentage || 0
              }}%</span>
            </div>
          </div>

          <!-- 詳細評価 -->
          <div
            v-if="hasDetailedScores"
            class="detailed-scores"
          >
            <div class="score-item">
              <v-icon
                color="orange"
                size="small"
              >
                mdi-silverware-fork-knife
              </v-icon>
              <span class="score-label">味</span>
              <v-rating
                :model-value="statistics.average_taste_score"
                color="orange"
                active-color="orange"
                size="x-small"
                readonly
                density="compact"
              />
              <span class="score-value">{{
                statistics.average_taste_score.toFixed(1)
              }}</span>
            </div>
            <div class="score-item">
              <v-icon
                color="blue"
                size="small"
              >
                mdi-puzzle
              </v-icon>
              <span class="score-label">難易度</span>
              <v-rating
                :model-value="statistics.average_difficulty_score"
                color="blue"
                active-color="blue"
                size="x-small"
                readonly
                density="compact"
              />
              <span class="score-value">{{
                statistics.average_difficulty_score.toFixed(1)
              }}</span>
            </div>
            <div class="score-item">
              <v-icon
                color="green"
                size="small"
              >
                mdi-format-list-numbered
              </v-icon>
              <span class="score-label">手順</span>
              <v-rating
                :model-value="statistics.average_instruction_clarity"
                color="green"
                active-color="green"
                size="x-small"
                readonly
                density="compact"
              />
              <span class="score-value">{{
                statistics.average_instruction_clarity.toFixed(1)
              }}</span>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- レビュー一覧 -->
    <div class="reviews-section">
      <div class="reviews-header">
        <h3 class="reviews-title">
          <v-icon
            color="primary"
            class="mr-2"
          >
            mdi-comment-text-multiple
          </v-icon>
          レビュー一覧
        </h3>
        <v-btn
          v-if="canWriteReview"
          color="primary"
          variant="outlined"
          size="small"
          @click="$emit('write-review')"
        >
          <v-icon start>
            mdi-pencil
          </v-icon>
          レビューを書く
        </v-btn>
      </div>

      <!-- ローディング状態 -->
      <div
        v-if="isLoading"
        class="loading-container"
      >
        <v-progress-circular
          indeterminate
          color="primary"
        />
        <p class="loading-text">
          レビューを読み込み中...
        </p>
      </div>

      <!-- レビューなし -->
      <div
        v-else-if="!reviews || reviews.length === 0"
        class="no-reviews"
      >
        <v-icon
          size="64"
          color="rgba(0, 0, 0, 0.3)"
        >
          mdi-comment-outline
        </v-icon>
        <p class="no-reviews-text">
          まだレビューがありません
        </p>
        <p class="no-reviews-subtitle">
          このレシピの感想を最初に投稿してみませんか？
        </p>
      </div>

      <!-- レビューリスト -->
      <div
        v-else
        class="reviews-list"
      >
        <v-card
          v-for="review in reviews"
          :key="review.id"
          class="review-card"
          elevation="2"
        >
          <v-card-text>
            <!-- レビューヘッダー -->
            <div class="review-header">
              <div class="user-info">
                <v-avatar
                  color="primary"
                  size="32"
                >
                  <span class="user-initial">{{
                    review.user.name.charAt(0)
                  }}</span>
                </v-avatar>
                <div class="user-details">
                  <span class="user-name">{{ review.user.name }}</span>
                  <span class="review-date">{{
                    formatDate(review.created_at)
                  }}</span>
                </div>
              </div>
              <div class="review-actions">
                <v-menu v-if="canEditReview(review)">
                  <template #activator="{ props: activatorProps }">
                    <v-btn
                      icon
                      size="small"
                      variant="text"
                      v-bind="activatorProps"
                    >
                      <v-icon>mdi-dots-vertical</v-icon>
                    </v-btn>
                  </template>
                  <v-list>
                    <v-list-item @click="$emit('edit-review', review)">
                      <v-list-item-title>
                        <v-icon start>
                          mdi-pencil
                        </v-icon>
                        編集
                      </v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="$emit('delete-review', review)">
                      <v-list-item-title>
                        <v-icon start>
                          mdi-delete
                        </v-icon>
                        削除
                      </v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </div>
            </div>

            <!-- 評価表示 -->
            <div class="review-rating">
              <v-rating
                :model-value="review.rating"
                color="primary"
                active-color="primary"
                size="small"
                readonly
                density="compact"
              />
              <span class="rating-text">{{ review.rating.toFixed(1) }}</span>
              <span
                v-if="review.average_score !== review.rating"
                class="average-score"
              >
                （総合: {{ review.average_score.toFixed(1) }}）
              </span>
            </div>

            <!-- 詳細評価 -->
            <div
              v-if="hasDetailedRatings(review)"
              class="detailed-ratings"
            >
              <div
                v-if="review.taste_score"
                class="detail-rating"
              >
                <v-icon
                  color="orange"
                  size="small"
                >
                  mdi-silverware-fork-knife
                </v-icon>
                <span class="detail-label">味</span>
                <v-rating
                  :model-value="review.taste_score"
                  color="orange"
                  active-color="orange"
                  size="x-small"
                  readonly
                  density="compact"
                />
              </div>
              <div
                v-if="review.difficulty_score"
                class="detail-rating"
              >
                <v-icon
                  color="blue"
                  size="small"
                >
                  mdi-puzzle
                </v-icon>
                <span class="detail-label">難易度</span>
                <v-rating
                  :model-value="review.difficulty_score"
                  color="blue"
                  active-color="blue"
                  size="x-small"
                  readonly
                  density="compact"
                />
              </div>
              <div
                v-if="review.instruction_clarity"
                class="detail-rating"
              >
                <v-icon
                  color="green"
                  size="small"
                >
                  mdi-format-list-numbered
                </v-icon>
                <span class="detail-label">手順</span>
                <v-rating
                  :model-value="review.instruction_clarity"
                  color="green"
                  active-color="green"
                  size="x-small"
                  readonly
                  density="compact"
                />
              </div>
            </div>

            <!-- コメント -->
            <div
              v-if="review.comment"
              class="review-comment"
            >
              <p>{{ review.comment }}</p>
            </div>

            <!-- レビュー画像 -->
            <div
              v-if="review.review_images && review.review_images.length > 0"
              class="review-images"
            >
              <v-chip
                v-for="(url, index) in review.review_images"
                :key="index"
                color="primary"
                variant="outlined"
                size="small"
                class="image-chip"
              >
                <v-icon start>
                  mdi-image
                </v-icon>
                画像 {{ index + 1 }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </div>

      <!-- ページネーション -->
      <div
        v-if="meta && meta.last_page > 1"
        class="pagination-container"
      >
        <v-pagination
          :model-value="meta.current_page"
          :length="meta.last_page"
          color="primary"
          @update:model-value="$emit('page-change', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Nuxt 3のauto-importを使用
/* eslint-disable */
// Nuxt 3のauto-importを使用し、TypeScriptエラーを無視
import { computed } from '#imports'
import type { RecipeReview, ReviewStatistics } from '~/types/review'

interface Props {
  reviews?: RecipeReview[]
  statistics?: ReviewStatistics
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  isLoading?: boolean
  canWriteReview?: boolean
  currentUserId?: number
}

interface Emits {
  (e: 'write-review'): void
  (e: 'edit-review', review: RecipeReview): void
  (e: 'delete-review', review: RecipeReview): void
  (e: 'page-change', page: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// 統計情報の詳細スコアがあるかどうか
const hasDetailedScores = computed(() => {
  if (!props.statistics) return false
  return (
    (props.statistics.average_taste_score &&
      props.statistics.average_taste_score > 0) ||
    (props.statistics.average_difficulty_score &&
      props.statistics.average_difficulty_score > 0) ||
    (props.statistics.average_instruction_clarity &&
      props.statistics.average_instruction_clarity > 0)
  )
})

// 個別レビューに詳細評価があるかどうか
const hasDetailedRatings = (review: RecipeReview): boolean => {
  return !!(
    review.taste_score ||
    review.difficulty_score ||
    review.instruction_clarity
  )
}

// レビュー編集権限チェック
const canEditReview = (review: RecipeReview): boolean => {
  return props.currentUserId === review.user.id
}

// 日付フォーマット
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<style scoped>
.review-list {
  margin-top: 24px;
}

.statistics-section {
  margin-bottom: 32px;
}

.statistics-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
}

.statistics-title {
  background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
  color: white;
  padding: 12px 16px;
  font-size: 16px;
}

.overall-rating {
  text-align: center;
  margin-bottom: 24px;
}

.rating-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.rating-number {
  font-size: 32px;
  font-weight: 700;
  color: #4caf50;
}

.review-count {
  font-size: 14px;
  color: #666;
}

.rating-distribution {
  margin-bottom: 24px;
}

.distribution-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.star-label {
  min-width: 40px;
  font-size: 14px;
  color: #666;
}

.distribution-bar {
  flex: 1;
}

.percentage {
  min-width: 50px;
  text-align: right;
  font-size: 14px;
  color: #666;
}

.detailed-scores {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.score-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.score-label {
  min-width: 50px;
  font-size: 14px;
  color: #666;
}

.score-value {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.reviews-section {
  margin-top: 32px;
}

.reviews-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.reviews-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
}

.loading-container,
.no-reviews {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
}

.loading-text {
  margin-top: 16px;
  color: #666;
}

.no-reviews-text {
  margin-top: 16px;
  font-size: 18px;
  color: #666;
  font-weight: 500;
}

.no-reviews-subtitle {
  margin-top: 8px;
  font-size: 14px;
  color: #999;
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.review-card {
  border-left: 4px solid #4caf50;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-initial {
  font-weight: 600;
  color: white;
  font-size: 14px;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.review-date {
  font-size: 12px;
  color: #999;
}

.review-rating {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.rating-text {
  font-weight: 500;
  color: #333;
}

.average-score {
  font-size: 12px;
  color: #666;
}

.detailed-ratings {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 12px;
}

.detail-rating {
  display: flex;
  align-items: center;
  gap: 4px;
}

.detail-label {
  font-size: 12px;
  color: #666;
  min-width: 30px;
}

.review-comment {
  margin-bottom: 12px;
}

.review-comment p {
  color: #333;
  line-height: 1.6;
  margin: 0;
}

.review-images {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.image-chip {
  font-size: 12px;
}

.pagination-container {
  display: flex;
  justify-content: center;
  margin-top: 32px;
}

/* レスポンシブ対応 */
@media (width <= 600px) {
  .reviews-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .rating-display {
    flex-direction: column;
    gap: 4px;
  }

  .distribution-row {
    gap: 8px;
  }

  .detailed-scores {
    gap: 4px;
  }

  .detailed-ratings {
    flex-direction: column;
    gap: 8px;
  }
}
</style>
