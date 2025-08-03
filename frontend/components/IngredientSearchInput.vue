<template>
  <div class="ingredient-search-input">
    <div class="search-container">
      <div class="search-input-wrapper">
        <v-icon
          class="search-icon"
          size="20"
          color="rgba(0, 0, 0, 0.6)"
        >
          mdi-magnify
        </v-icon>
        <input
          v-model="searchQuery"
          placeholder="食材を検索..."
          class="search-input"
          @input="onSearchInput"
          @focus="showSuggestions = true"
          @blur="hideSuggestions"
        >
        <v-btn
          v-if="searchQuery"
          icon
          size="small"
          variant="text"
          class="clear-btn"
          @click="clearSearch"
        >
          <v-icon size="16">
            mdi-close
          </v-icon>
        </v-btn>
      </div>

      <!-- 検索候補リスト -->
      <div
        v-if="showSuggestions && suggestions.length > 0"
        class="suggestions-container"
      >
        <div
          v-for="suggestion in suggestions"
          :key="suggestion.id"
          class="suggestion-item"
          @mousedown="selectIngredient(suggestion)"
        >
          <span class="suggestion-name">{{ suggestion.name }}</span>
          <v-icon
            v-if="suggestion.isPopular"
            size="16"
            color="#4CAF50"
          >
            mdi-star
          </v-icon>
        </div>
      </div>

      <!-- 検索中のローディング -->
      <div
        v-if="isSearching && showSuggestions"
        class="loading-container"
      >
        <v-progress-circular
          indeterminate
          size="20"
          color="primary"
        />
        <span class="loading-text">検索中...</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onUnmounted } from 'vue'
import { useIngredientsStore } from '~/stores/ingredients'
import { useRecipeApi } from '~/composables/useRecipeApi'
import type { Ingredient } from '~/types/ingredient'

const ingredientsStore = useIngredientsStore()
const { searchIngredients: apiSearchIngredients } = useRecipeApi()

const searchQuery = ref('')
const showSuggestions = ref(false)
const suggestions = ref<Ingredient[]>([])
const isSearching = ref(false)

let searchTimeout: NodeJS.Timeout | null = null

const onSearchInput = () => {
  const query = searchQuery.value.trim()

  if (!query) {
    suggestions.value = []
    isSearching.value = false
    return
  }

  // デバウンス処理
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  isSearching.value = true

  searchTimeout = setTimeout(async () => {
    try {
      const ingredients = await apiSearchIngredients(query)

      // 人気の食材にマークを付ける（今後の実装で使用）
      suggestions.value = ingredients.map(ingredient => ({
        ...ingredient,
        isPopular: false,
      }))
    }
    catch (error) {
      const config = useRuntimeConfig()
      const isDevelopment = config.public.appEnv === 'development'
      if (isDevelopment) {
        console.error('検索エラー:', error)
      }
      suggestions.value = []
    }
    finally {
      isSearching.value = false
    }
  }, 300)
}

const selectIngredient = (ingredient: Ingredient) => {
  ingredientsStore.addIngredient(ingredient)
  clearSearch()
}

const clearSearch = () => {
  searchQuery.value = ''
  suggestions.value = []
  showSuggestions.value = false
  isSearching.value = false
}

const hideSuggestions = () => {
  // 少し遅延させて、クリックイベントを先に処理
  setTimeout(() => {
    showSuggestions.value = false
  }, 100)
}

onUnmounted(() => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
})
</script>

<style scoped>
.ingredient-search-input {
  position: relative;
  width: 100%;
}

.search-container {
  position: relative;
  width: 100%;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background-color: #f5f5f5;
  border-radius: 8px;
  padding: 12px 16px;
  border: 2px solid transparent;
  transition: border-color 0.2s ease;
}

.search-input-wrapper:focus-within {
  border-color: #4caf50;
  background-color: #fff;
}

.search-icon {
  margin-right: 8px;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 16px;
  color: #333;
}

.search-input::placeholder {
  color: rgba(0, 0, 0, 0.6);
}

.clear-btn {
  margin-left: 8px;
}

.suggestions-container {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  margin-top: 4px;
}

.suggestion-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s ease;
}

.suggestion-item:hover {
  background-color: #f8f9fa;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-name {
  font-size: 14px;
  color: #333;
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background-color: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  margin-top: 4px;
}

.loading-text {
  margin-left: 8px;
  font-size: 14px;
  color: rgba(0, 0, 0, 0.6);
}
</style>
