<template>
  <div class="recipe-detail">
    <!-- ヘッダー -->
    <div class="header">
      <div class="header-top">
        <v-btn 
          @click="goBack" 
          icon 
          variant="text" 
          class="back-btn"
        >
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <div class="header-actions">
          <FavoriteButton 
            v-if="recipe"
            :recipe-id="recipe.id" 
            class="favorite-btn"
          />
          <v-btn icon variant="text" class="share-btn" @click="shareRecipe">
            <v-icon>mdi-share-variant</v-icon>
          </v-btn>
        </div>
      </div>
      <div class="recipe-title-section">
        <h1 class="page-title" :title="recipe?.name">{{ recipe?.name || 'レシピ詳細' }}</h1>
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
        <div class="placeholder-image">
          <v-icon size="48" color="rgba(76, 175, 80, 0.6)">mdi-chef-hat</v-icon>
          <span class="placeholder-text">レシピ画像</span>
        </div>
      </div>

      <!-- レシピ情報 -->
      <div class="recipe-info">
        <div class="recipe-stats">
          <div class="stat-item">
            <v-icon color="#666">mdi-clock-outline</v-icon>
            <span>{{ recipe.cooking_time }}分</span>
          </div>
          <div class="stat-item">
            <v-icon color="#666">mdi-account-multiple</v-icon>
            <span>2人分</span>
          </div>
          <div class="stat-item">
            <v-icon color="#666">mdi-fire</v-icon>
            <span>300kcal</span>
          </div>
        </div>
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
            <div class="ingredient-status">
              <v-icon 
                :color="isIngredientAvailable(ingredient.id) ? '#4CAF50' : '#FF9800'" 
                size="20"
              >
                {{ isIngredientAvailable(ingredient.id) ? 'mdi-check-circle' : 'mdi-alert' }}
              </v-icon>
            </div>
            <div class="ingredient-info">
              <span class="ingredient-name">{{ ingredient.name }}</span>
              <span class="ingredient-quantity">{{ ingredient.quantity }}</span>
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
            <div class="instruction-checkbox">
              <v-checkbox
                :model-value="isStepCompleted(index)"
                @update:model-value="toggleStep(index)"
                color="primary"
                hide-details
                density="comfortable"
              />
            </div>
            <div class="instruction-content">
              <span class="step-number">{{ index + 1 }}.</span>
              <span 
                :class="['instruction-text', { 'completed': isStepCompleted(index) }]"
              >
                {{ instruction }}
              </span>
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
            @change="toggleScreenWakeLock"
            color="primary"
            hide-details
            density="comfortable"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useIngredientsStore } from '~/stores/ingredients'
import { useRecipesStore } from '~/stores/recipes'
import { useRecipeApi } from '~/composables/useRecipeApi'
import type { RecipeDetail } from '~/types/recipe'

const route = useRoute()
const recipeId = route.params.id as string

const ingredientsStore = useIngredientsStore()
const recipesStore = useRecipesStore()
const { getRecipeDetail } = useRecipeApi()

const { selectedIngredientIds } = storeToRefs(ingredientsStore)
const { 
  currentRecipe, 
  completedSteps, 
  keepScreenOn, 
  completedStepsCount, 
  totalStepsCount, 
  progressPercentage 
} = storeToRefs(recipesStore)

const recipe = computed(() => currentRecipe.value)
const isLoading = ref(true)

const isIngredientAvailable = (ingredientId: number) => {
  return selectedIngredientIds.value.includes(ingredientId)
}

const isStepCompleted = (stepIndex: number) => {
  return recipesStore.isStepCompleted(stepIndex)
}

const toggleStep = (stepIndex: number) => {
  recipesStore.toggleStep(stepIndex)
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

const goBack = () => {
  history.back()
}

const shareRecipe = async () => {
  if (!recipe.value) return
  
  const shareData = {
    title: recipe.value.name,
    text: `${recipe.value.name}のレシピをチェック！`,
    url: window.location.href
  }
  
  try {
    if (navigator.share) {
      await navigator.share(shareData)
    } else {
      // フォールバック: クリップボードにコピー
      await navigator.clipboard.writeText(window.location.href)
    }
  } catch (error) {
    // ユーザーがキャンセルした場合はエラーではないので何もしない
    if (error.name !== 'AbortError') {
      const config = useRuntimeConfig()
      const isDevelopment = config.public.appEnv === 'development'
      if (isDevelopment) {
        // eslint-disable-next-line no-console
        console.error('共有エラー:', error)
      }
    }
  }
}

onMounted(() => {
  fetchRecipe()
})

onUnmounted(() => {
  recipesStore.releaseWakeLock()
})

// SEOとメタデータ
useHead({
  title: computed(() => `${recipe.value?.name || 'レシピ詳細'} - Reliche`),
  meta: [
    { name: 'description', content: computed(() => `${recipe.value?.name || 'レシピ'}の詳細な作り方と材料`) }
  ]
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
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #666;
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
  gap: 12px;
  padding: 12px 16px;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.ingredient-item:hover {
  background-color: #f8f9fa;
}

.ingredient-status {
  flex-shrink: 0;
}

.ingredient-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
}

.ingredient-name {
  font-size: 16px;
  color: #333;
}

.ingredient-quantity {
  font-size: 14px;
  color: #666;
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
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #f0f0f0;
  margin-bottom: 12px;
  transition: all 0.3s ease;
}

.instruction-item:last-child {
  margin-bottom: 0;
}

.instruction-item:hover {
  border-color: #4CAF50;
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