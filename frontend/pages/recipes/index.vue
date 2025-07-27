<template>
  <div class="recipes-page">
    <!-- ヘッダー -->
    <div class="header">
      <v-btn icon variant="text" class="back-btn" @click="goBack">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      <h1 class="page-title">レシピ一覧</h1>
      <div class="header-actions">
        <v-btn
          icon
          variant="text"
          class="ai-generate-btn"
          :title="'AI レシピ生成'"
          @click="goToAIGenerate"
        >
          <v-icon>mdi-robot-excited</v-icon>
        </v-btn>
        <v-btn icon variant="text" class="search-btn" @click="toggleSearch">
          <v-icon>mdi-magnify</v-icon>
        </v-btn>
      </div>
    </div>

    <!-- 検索バー -->
    <div v-if="showSearch" class="search-section">
      <div class="search-input-wrapper">
        <v-text-field
          v-model="searchQuery"
          placeholder="レシピ名で検索..."
          prepend-inner-icon="mdi-magnify"
          append-inner-icon="mdi-close"
          variant="outlined"
          density="compact"
          hide-details
          class="search-input"
          @click:append-inner="clearSearch"
          @keyup.enter="performSearch"
        />
      </div>
    </div>

    <!-- レシピ検索結果サマリー -->
    <div class="result-summary">
      <p v-if="recipes.length > 0">
        <span v-if="selectedCount > 0">
          {{ selectedCount }}品目で作れるレシピ
          {{ recipes.length }}件見つかりました
        </span>
        <span v-else> 全レシピ {{ recipes.length }}件を表示中 </span>
      </p>
      <p v-else-if="!isLoading">レシピが見つかりませんでした</p>
    </div>

    <!-- フィルター -->
    <div class="filters-section">
      <div class="filter-chips">
        <v-chip-group v-model="selectedFilter" color="primary">
          <v-chip
            v-for="filter in filters"
            :key="filter.key"
            :value="filter.key"
            variant="outlined"
          >
            {{ filter.label }}
          </v-chip>
        </v-chip-group>
      </div>

      <div class="sort-section">
        <v-select
          v-model="sortBy"
          :items="sortOptions"
          item-title="label"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          class="sort-select"
          prepend-inner-icon="mdi-sort"
        />
      </div>
    </div>

    <!-- レシピリスト -->
    <div class="recipes-container">
      <div v-if="isLoading" class="loading-container">
        <v-progress-circular indeterminate size="40" color="primary" />
        <p class="loading-text">レシピを検索中...</p>
      </div>

      <div v-else-if="recipes.length === 0" class="empty-state">
        <v-icon size="64" color="rgba(0, 0, 0, 0.3)">mdi-chef-hat</v-icon>
        <p class="empty-text">レシピが見つかりませんでした</p>
        <p class="empty-subtext">食材を変更して再度お試しください</p>
      </div>

      <div v-else class="recipes-grid">
        <RecipeCard
          v-for="recipe in filteredRecipes"
          :key="recipe.id"
          :recipe="recipe"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useIngredientsStore } from '~/stores/ingredients'
import { useRecipeApi } from '~/composables/useRecipeApi'
import RecipeCard from '~/components/RecipeCard.vue'
import type { Recipe } from '~/types/recipe'

const ingredientsStore = useIngredientsStore()
const { selectedIngredients, selectedCount, selectedIngredientIds } =
  storeToRefs(ingredientsStore)
const { getAllRecipes, suggestRecipes } = useRecipeApi()

const recipes = ref<Recipe[]>([])
const allRecipes = ref<Recipe[]>([])
const isLoading = ref(false)
const selectedFilter = ref(null)
const sortBy = ref('compatibility')
const showSearch = ref(false)
const searchQuery = ref('')

const filters = [
  { key: 'quick', label: '時短' },
  { key: 'economical', label: '節約' },
  { key: 'healthy', label: 'ヘルシー' },
]

const sortOptions = [
  { label: '適合率順', value: 'compatibility' },
  { label: '調理時間順', value: 'cooking_time' },
  { label: '人気順', value: 'popularity' },
]

const filteredRecipes = computed(() => {
  let filtered = [...recipes.value]

  // フィルター適用（現在はプレースホルダー）
  if (selectedFilter.value) {
    switch (selectedFilter.value) {
      case 'quick':
        filtered = filtered.filter((recipe) => recipe.cooking_time <= 15)
        break
      case 'economical':
        // 不足食材が少ない（手持ち食材で作りやすい）レシピを優先
        filtered = filtered.filter((recipe) => recipe.missing_count <= 2)
        break
      case 'healthy':
        // 調理時間が長め（手間をかけた）レシピをヘルシーと判定
        filtered = filtered.filter((recipe) => recipe.cooking_time >= 20)
        break
    }
  }

  // ソート適用
  switch (sortBy.value) {
    case 'compatibility':
      filtered.sort((a, b) => {
        if (a.missing_count !== b.missing_count) {
          return a.missing_count - b.missing_count
        }
        return a.cooking_time - b.cooking_time
      })
      break
    case 'cooking_time':
      filtered.sort((a, b) => a.cooking_time - b.cooking_time)
      break
    case 'popularity':
      // 人気度スコア = (10 - 不足食材数) + (30 - 調理時間) / 10
      // 作りやすさと時短性を組み合わせた総合的な人気度指標
      filtered.sort((a, b) => {
        const scoreA = 10 - a.missing_count + (30 - a.cooking_time) / 10
        const scoreB = 10 - b.missing_count + (30 - b.cooking_time) / 10
        return scoreB - scoreA
      })
      break
  }

  return filtered
})

const fetchRecipes = async () => {
  isLoading.value = true

  try {
    let data
    if (selectedIngredientIds.value.length === 0) {
      // 食材が選択されていない場合は全レシピを取得
      data = await getAllRecipes()
    } else {
      // 食材が選択されている場合は提案レシピを取得
      data = await suggestRecipes(selectedIngredientIds.value)
    }
    recipes.value = data
    allRecipes.value = [...data]
  } catch (error) {
    const config = useRuntimeConfig()
    const isDevelopment = config.public.appEnv === 'development'
    if (isDevelopment) {
      // eslint-disable-next-line no-console
      console.error('レシピ取得エラー:', error)
    }
    recipes.value = []
    allRecipes.value = []
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  navigateTo('/')
}

const goToAIGenerate = () => {
  navigateTo('/recipes/generate')
}

const toggleSearch = () => {
  showSearch.value = !showSearch.value
  if (!showSearch.value) {
    clearSearch()
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  performSearch()
}

const performSearch = () => {
  if (!searchQuery.value.trim()) {
    recipes.value = [...allRecipes.value]
  } else {
    recipes.value = allRecipes.value.filter((recipe) =>
      recipe.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
}

watch(searchQuery, () => {
  performSearch()
})

onMounted(() => {
  fetchRecipes()
})

// SEOとメタデータ
useHead({
  title: 'レシピ一覧 - Reliche',
  meta: [
    { name: 'description', content: '選択した食材に基づくおすすめレシピ一覧' },
  ],
})
</script>

<style scoped>
.recipes-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e8 100%);
  padding: 16px;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  padding: 0 4px;
}

.back-btn {
  color: #666;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #2e7d32;
  margin: 0;
  flex: 1;
  text-align: center;
  margin-left: -48px; /* バックボタンの幅分調整 */
}

.header-actions {
  display: flex;
  gap: 8px;
}

.ai-generate-btn {
  color: #4caf50;
}

.search-btn {
  color: #666;
}

.search-section {
  margin-bottom: 20px;
}

.search-input-wrapper {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.search-input {
  background: transparent;
}

.result-summary {
  margin-bottom: 20px;
  padding: 0 4px;
}

.result-summary p {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.filters-section {
  margin-bottom: 24px;
}

.filter-chips {
  margin-bottom: 16px;
}

.sort-section {
  display: flex;
  justify-content: flex-end;
}

.sort-select {
  width: 160px;
}

.recipes-container {
  min-height: 400px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 32px;
}

.loading-text {
  margin-top: 16px;
  font-size: 16px;
  color: #666;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 32px;
  text-align: center;
}

.empty-text {
  margin-top: 16px;
  font-size: 18px;
  color: #333;
  font-weight: 500;
}

.empty-subtext {
  margin-top: 8px;
  font-size: 14px;
  color: #666;
}

.recipes-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

/* タブレット・デスクトップ対応 */
@media (min-width: 768px) {
  .recipes-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 24px;
  }

  .page-title {
    font-size: 28px;
  }

  .recipes-grid {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
  }

  .filters-section {
    margin-bottom: 32px;
  }

  .filter-chips {
    margin-bottom: 20px;
  }

  .sort-select {
    width: 180px;
  }
}

@media (min-width: 1024px) {
  .recipes-grid {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  }
}
</style>
