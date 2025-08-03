<template>
  <div class="recipe-detail">
    <!-- ヘッダー -->
    <div class="header">
      <div class="header-top">
        <v-btn icon variant="text" class="back-btn" @click="goBack">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <div class="header-actions">
          <v-btn icon variant="text" class="share-btn" @click="shareRecipe">
            <v-icon>mdi-share-variant</v-icon>
          </v-btn>
        </div>
      </div>
      <div class="recipe-title-section">
        <h1 class="page-title" :title="recipe?.name || 'レシピ詳細'">
          {{ recipe?.name || 'レシピ詳細' }}
        </h1>
      </div>
    </div>

    <div v-if="isLoading" class="loading-container">
      <v-progress-circular indeterminate size="40" color="primary" />
      <p class="loading-text">レシピを読み込み中...</p>
    </div>

    <div v-else-if="!recipe" class="error-container">
      <v-icon size="64" color="rgba(0, 0, 0, 0.3)">mdi-alert</v-icon>
      <p class="error-text">レシピが見つかりませんでした</p>
    </div>

    <div v-else class="recipe-content">
      <!-- レシピ画像 -->
      <div class="recipe-image">
        <v-img
          v-if="recipe.image_url"
          :src="recipe.image_url"
          :alt="recipe.name"
          height="300"
          cover
          class="recipe-main-image"
        >
          <template #placeholder>
            <div class="image-loading">
              <v-progress-circular indeterminate size="40" color="primary" />
            </div>
          </template>
        </v-img>
        <div v-else class="placeholder-image">
          <v-icon size="48" color="rgba(76, 175, 80, 0.6)">mdi-chef-hat</v-icon>
          <span class="placeholder-text">レシピ画像</span>
        </div>
      </div>

      <!-- レシピ情報 -->
      <div class="recipe-info">
        <v-row class="recipe-stats-grid" no-gutters>
          <v-col cols="6" sm="3">
            <v-card class="stat-card" elevation="2">
              <div class="stat-content">
                <v-icon color="#4CAF50" size="24">mdi-clock-outline</v-icon>
                <div class="stat-text">
                  <span class="stat-value">{{ recipe.cooking_time }}</span>
                  <span class="stat-label">分</span>
                </div>
              </div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card class="stat-card" elevation="2">
              <div class="stat-content">
                <v-icon color="#FF9800" size="24">mdi-account-multiple</v-icon>
                <div class="stat-text">
                  <span class="stat-value">2</span>
                  <span class="stat-label">人分</span>
                </div>
              </div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card class="stat-card" elevation="2">
              <div class="stat-content">
                <v-icon color="#F44336" size="24">mdi-fire</v-icon>
                <div class="stat-text">
                  <span class="stat-value">300</span>
                  <span class="stat-label">kcal</span>
                </div>
              </div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="3">
            <v-card class="stat-card" elevation="2">
              <div class="stat-content">
                <v-icon color="#9C27B0" size="24">mdi-tag-outline</v-icon>
                <div class="stat-text">
                  <span class="stat-value">和食</span>
                  <span class="stat-label">ジャンル</span>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>
      </div>

      <!-- 材料 -->
      <div class="ingredients-section">
        <h2 class="section-title">材料</h2>
        <div class="ingredients-list">
          <div
            v-for="ingredient in recipe.ingredients"
            :key="ingredient.id"
            class="ingredient-item"
          >
            <div class="ingredient-info">
              <span class="ingredient-name">{{ ingredient.name }}</span>
              <span class="ingredient-amount">{{ ingredient.amount }}</span>
            </div>
            <div class="ingredient-checkbox">
              <v-checkbox
                :model-value="isIngredientChecked(ingredient.id)"
                color="primary"
                hide-details
                density="comfortable"
                @update:model-value="toggleIngredient(ingredient.id)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 作り方 -->
      <div class="instructions-section">
        <div class="instructions-header">
          <h2 class="section-title">作り方</h2>
          <div class="progress-info">
            <span class="progress-text">
              {{ completedStepsCount }}/{{ totalStepsCount }}
            </span>
            <v-progress-linear
              :model-value="progressPercentage"
              color="primary"
              height="6"
              rounded
              class="progress-bar"
            />
          </div>
        </div>

        <div class="instructions-list">
          <div
            v-for="(instruction, index) in recipe.instructions"
            :key="index"
            class="instruction-item"
          >
            <div class="instruction-content">
              <span class="step-number">{{ index + 1 }}.</span>
              <span
                :class="[
                  'instruction-text',
                  { completed: isStepCompleted(index) },
                ]"
              >
                {{ instruction }}
              </span>
            </div>
            <div class="instruction-checkbox">
              <v-checkbox
                :model-value="isStepCompleted(index)"
                color="primary"
                hide-details
                density="comfortable"
                @update:model-value="toggleStep(index)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 画面スリープ防止 -->
      <div class="wake-lock-section">
        <div class="wake-lock-toggle">
          <v-icon color="#666">mdi-lightbulb-outline</v-icon>
          <span class="wake-lock-label">画面スリープ防止</span>
          <v-switch
            v-model="keepScreenOn"
            color="primary"
            hide-details
            density="comfortable"
            @change="toggleScreenWakeLock"
          />
        </div>
      </div>

      <!-- レビューセクション -->
      <div class="reviews-section">
        <ReviewList
          :reviews="reviews"
          :statistics="reviewStatistics"
          :meta="reviewMeta"
          :is-loading="isLoadingReviews"
          :can-write-review="canWriteReview"
          :current-user-id="currentUser?.id"
          @write-review="openReviewForm"
          @edit-review="editReview"
          @delete-review="deleteReview"
          @page-change="loadReviews"
        />
      </div>
    </div>

    <!-- レビューフォームダイアログ -->
    <v-dialog v-model="showReviewForm" max-width="600px" persistent>
      <ReviewForm
        :recipe-id="recipeId"
        :existing-review="editingReview"
        @success="onReviewSuccess"
        @cancel="closeReviewForm"
        @error="onReviewError"
      />
    </v-dialog>

    <!-- 削除確認ダイアログ -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="delete-title">
          <v-icon color="error" class="mr-2">mdi-alert</v-icon>
          レビューを削除
        </v-card-title>
        <v-card-text>
          <p>このレビューを削除しますか？</p>
          <p class="delete-warning">この操作は取り消すことができません。</p>
        </v-card-text>
        <v-card-actions>
          <v-btn variant="outlined" @click="showDeleteDialog = false">
            キャンセル
          </v-btn>
          <v-spacer />
          <v-btn
            color="error"
            :loading="isDeletingReview"
            @click="confirmDeleteReview"
          >
            削除する
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- スナックバー -->
    <v-snackbar v-model="showSnackbar" :color="snackbarColor" timeout="4000">
      {{ snackbarMessage }}
      <template #actions>
        <v-btn variant="text" @click="showSnackbar = false"> 閉じる </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useRoute, useRuntimeConfig, navigateTo, useHead } from 'nuxt/app'
import { computed, onMounted, onUnmounted, ref } from '#imports'
import ReviewForm from '~/components/ReviewForm.vue'
import ReviewList from '~/components/ReviewList.vue'
import { useRecipeApi } from '~/composables/useRecipeApi'
import { useReviewApi } from '~/composables/useReviewApi'
import { useAuthStore } from '~/stores/auth'
import { useIngredientsStore } from '~/stores/ingredients'
import { useRecipesStore } from '~/stores/recipes'
import type { RecipeReview, ReviewStatistics } from '~/types/review'

const route = useRoute()
const recipeId = route.params.id as string

const ingredientsStore = useIngredientsStore()
const recipesStore = useRecipesStore()
const authStore = useAuthStore()
const { getRecipeDetail } = useRecipeApi()
const {
  getReviews,
  getStatistics,
  deleteReview: deleteReviewApi,
} = useReviewApi()

const { selectedIngredientIds } = storeToRefs(ingredientsStore)
const { currentRecipe, completedSteps } = storeToRefs(recipesStore)
const { currentUser, isAuthenticated } = storeToRefs(authStore)

const recipe = computed(() => currentRecipe.value)
const isLoading = ref(true)

// レビュー統計の取得
const reviewStatistics = ref(null)

// 材料チェック状態管理
const checkedIngredients = ref<Set<number>>(new Set())

// 材料がチェックされているかどうか
const isIngredientChecked = (id: number): boolean => {
  return checkedIngredients.value.has(id)
}

// 手順チェック状態管理
const checkedSteps = ref<Set<number>>(new Set())

// 手順の進捗管理
const completedStepsCount = computed(() => checkedSteps.value.size)
const totalStepsCount = computed(() => recipe.value?.instructions?.length || 0)
const progressPercentage = computed(() => {
  if (totalStepsCount.value === 0) return 0
  return Math.round((completedStepsCount.value / totalStepsCount.value) * 100)
})

// 画面スリープ防止
const keepScreenOn = ref(false)

// 各種ハンドラー関数
const goBack = () => {
  history.back()
}

const shareRecipe = async () => {
  if (!recipe.value) return

  const shareData = {
    title: recipe.value.name,
    text: `${recipe.value.name}のレシピをチェック！`,
    url: window.location.href,
  }

  try {
    if (navigator.share) {
      await navigator.share(shareData)
    } else {
      // フォールバック: クリップボードにコピー
      await navigator.clipboard.writeText(window.location.href)
      showMessage('URLをクリップボードにコピーしました', 'success')
    }
  } catch (error: any) {
    // ユーザーがキャンセルした場合はエラーではないので何もしない
    if (error.name !== 'AbortError') {
      console.error('共有エラー:', error)
    }
  }
}

// 材料のチェック状態を切り替え
const toggleIngredient = (id: number) => {
  if (checkedIngredients.value.has(id)) {
    checkedIngredients.value.delete(id)
  } else {
    checkedIngredients.value.add(id)
  }
  // Setの変更を検知させるため新しいSetを作成
  checkedIngredients.value = new Set(checkedIngredients.value)
}

// 手順がチェックされているかどうか
const isStepCompleted = (index: number): boolean => {
  return checkedSteps.value.has(index)
}

// 手順のチェック状態を切り替え
const toggleStep = (index: number) => {
  if (checkedSteps.value.has(index)) {
    checkedSteps.value.delete(index)
  } else {
    checkedSteps.value.add(index)
  }
  // Setの変更を検知させるため新しいSetを作成
  checkedSteps.value = new Set(checkedSteps.value)
}

// レビュー関連の状態
const reviews = ref<RecipeReview[]>([])
const reviewMeta = ref<{
  current_page: number
  last_page: number
  per_page: number
  total: number
} | null>(null)
const isLoadingReviews = ref(false)
const showReviewForm = ref(false)
const showDeleteDialog = ref(false)
const editingReview = ref<RecipeReview | undefined>(undefined)
const deletingReview = ref<RecipeReview | null>(null)
const isDeletingReview = ref(false)

// スナックバー
const showSnackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref<'success' | 'error'>('success')

// レビュー機能の権限チェック
const canWriteReview = computed(() => {
  return Boolean(isAuthenticated.value && recipe.value)
})

// 材料の利用可能性チェック
const isIngredientAvailable = (ingredientId: number) => {
  return selectedIngredientIds.value.includes(ingredientId)
}

const toggleScreenWakeLock = () => {
  recipesStore.toggleScreenWakeLock()
}

const fetchRecipe = async () => {
  isLoading.value = true

  try {
    const recipe = await getRecipeDetail(recipeId)
    if (recipe && recipe.instructions && Array.isArray(recipe.instructions)) {
      recipesStore.setCurrentRecipe(recipe)
    } else {
      throw new Error('レシピデータが不正です')
    }
  } catch (error) {
    const config = useRuntimeConfig()
    const isDevelopment = config.public.appEnv === 'development'
    if (isDevelopment) {
      // eslint-disable-next-line no-console
      console.error('レシピ取得エラー:', error)
    }
    await navigateTo('/recipes')
  } finally {
    isLoading.value = false
  }
}

// レビュー一覧と統計の読み込み
const loadReviews = async (page = 1) => {
  isLoadingReviews.value = true

  try {
    const [reviewsData, statisticsData] = await Promise.all([
      getReviews(recipeId, page),
      getStatistics(recipeId),
    ])

    reviews.value = reviewsData.data
    reviewMeta.value = reviewsData.meta
    reviewStatistics.value = statisticsData
  } catch (error) {
    const config = useRuntimeConfig()
    const isDevelopment = config.public.appEnv === 'development'
    if (isDevelopment) {
      // eslint-disable-next-line no-console
      console.error('レビュー取得エラー:', error)
    }
    showMessage('レビューの読み込みに失敗しました', 'error')
  } finally {
    isLoadingReviews.value = false
  }
}

// レビューフォーム関連
const openReviewForm = () => {
  editingReview.value = undefined
  showReviewForm.value = true
}

const editReview = (review: RecipeReview) => {
  editingReview.value = review
  showReviewForm.value = true
}

const closeReviewForm = () => {
  showReviewForm.value = false
  editingReview.value = undefined
}

const onReviewSuccess = (review: RecipeReview) => {
  closeReviewForm()
  showMessage(
    editingReview.value ? 'レビューを更新しました' : 'レビューを投稿しました',
    'success'
  )
  loadReviews() // レビュー一覧を再読み込み
}

const onReviewError = (error: string) => {
  showMessage(error, 'error')
}

// レビュー削除
const deleteReview = (review: RecipeReview) => {
  deletingReview.value = review
  showDeleteDialog.value = true
}

const confirmDeleteReview = async () => {
  if (!deletingReview.value) return

  isDeletingReview.value = true

  try {
    await deleteReviewApi(recipeId, deletingReview.value.id)
    showMessage('レビューを削除しました', 'success')
    loadReviews() // レビュー一覧を再読み込み
  } catch (error: unknown) {
    const errorMessage =
      error instanceof Error ? error.message : 'レビューの削除に失敗しました'
    showMessage(errorMessage, 'error')
  } finally {
    isDeletingReview.value = false
    showDeleteDialog.value = false
    deletingReview.value = null
  }
}

// スナックバーメッセージ表示
const showMessage = (
  message: string,
  color: 'success' | 'error' = 'success'
) => {
  snackbarMessage.value = message
  snackbarColor.value = color
  showSnackbar.value = true
}

onMounted(async () => {
  await fetchRecipe()
  await loadReviews()
})

onUnmounted(() => {
  recipesStore.releaseWakeLock()
})

// SEOとメタデータ
useHead({
  title: computed(() => `${recipe.value?.name || 'レシピ詳細'} - Reliche`),
  meta: [
    {
      name: 'description',
      content: computed(
        () => `${recipe.value?.name || 'レシピ'}の詳細な作り方と材料`
      ),
    },
  ],
})
</script>

<style scoped>
.recipe-detail {
  min-height: 100vh;
  background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e8 100%);
  padding: 16px;
}

.header {
  margin-bottom: 20px;
  padding: 0 4px;
}

.header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.back-btn {
  color: #2e7d32 !important;
}

.recipe-title-section {
  padding: 0 8px;
}

.page-title {
  font-size: 20px;
  font-weight: 600;
  color: #2e7d32;
  margin: 0;
  text-align: center;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-height: 2.6em;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.share-btn {
  color: #2e7d32 !important;
}

.loading-container,
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 32px;
}

.loading-text,
.error-text {
  margin-top: 16px;
  font-size: 16px;
  color: #666;
}

.recipe-content {
  max-width: 800px;
  margin: 0 auto;
}

.recipe-image {
  height: 200px;
  background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  border: 2px dashed rgba(76, 175, 80, 0.3);
}

.placeholder-image {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.placeholder-text {
  font-size: 14px;
  color: rgba(76, 175, 80, 0.8);
  font-weight: 500;
}

.recipe-info {
  margin-bottom: 32px;
}

.recipe-stats {
  display: flex;
  justify-content: center;
  gap: 32px;
  padding: 16px;
  background: white;
  border-radius: 12px;
}

.stat-card {
  margin: 4px;
  border-radius: 12px !important;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.stat-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 12px;
  gap: 8px;
}

.stat-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-value {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  line-height: 1.2;
}

.stat-label {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}

.ingredients-section,
.instructions-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}

.ingredients-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ingredient-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-radius: 8px;
  transition: background-color 0.2s ease;
  border: 1px solid #f0f0f0;
  margin-bottom: 8px;
}

.ingredient-item:hover {
  background-color: #f8f9fa;
  border-color: #e0e0e0;
}

.ingredient-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
  margin-right: 12px;
}

.ingredient-name {
  font-size: 16px;
  color: #333;
  font-weight: 500;
}

.ingredient-amount {
  font-size: 14px;
  color: #666;
  margin-left: 8px;
}

.ingredient-checkbox {
  flex-shrink: 0;
}

.instructions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.progress-info {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 120px;
}

.progress-text {
  font-size: 14px;
  color: #666;
  white-space: nowrap;
}

.progress-bar {
  width: 80px;
}

.instructions-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.instruction-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #f0f0f0;
  margin-bottom: 12px;
  transition: all 0.2s ease;
}

.instruction-item:hover {
  background-color: #f8f9fa;
  border-color: #e0e0e0;
}

.instruction-content {
  flex: 1;
  margin-right: 12px;
}

.instruction-checkbox {
  flex-shrink: 0;
}

.instruction-item:last-child {
  margin-bottom: 0;
}

.instruction-item:hover {
  border-color: #4caf50;
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.1);
}

.instruction-checkbox {
  flex-shrink: 0;
  padding-top: 2px;
}

.instruction-content {
  display: flex;
  flex: 1;
  gap: 8px;
  align-items: flex-start;
}

.step-number {
  font-weight: 600;
  color: #333;
  flex-shrink: 0;
}

.instruction-text {
  line-height: 1.6;
  color: #333;
  transition: all 0.3s ease;
}

.instruction-text.completed {
  color: #999;
  text-decoration: line-through;
}

.wake-lock-section {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.media-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.wake-lock-toggle {
  display: flex;
  align-items: center;
  gap: 12px;
}

.wake-lock-label {
  flex: 1;
  font-size: 16px;
  color: #333;
}

.reviews-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-top: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.delete-title {
  background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
  color: white;
}

.delete-warning {
  color: #f44336;
  font-size: 14px;
  margin-top: 8px;
  margin-bottom: 0;
}

/* タブレット・デスクトップ対応 */
@media (min-width: 768px) {
  .recipe-detail {
    padding: 24px;
  }

  .page-title {
    font-size: 24px;
  }

  .recipe-image {
    height: 250px;
  }

  .recipe-stats {
    gap: 48px;
  }

  .stat-item {
    font-size: 16px;
  }

  .ingredients-section,
  .instructions-section {
    padding: 32px;
  }
}
</style>
