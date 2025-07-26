<template>
  <div class="ingredient-registration">
    <!-- ヘッダー -->
    <div class="header">
      <h1 class="page-title">食材登録</h1>
    </div>

    <!-- 検索バー -->
    <div class="search-section">
      <IngredientSearchInput />
    </div>

    <!-- 登録済み食材 -->
    <div class="selected-ingredients-section">
      <h2 class="section-title">登録済み食材</h2>
      <div class="ingredients-container">
        <div v-if="selectedIngredients.length === 0" class="empty-state">
          <v-icon size="48" color="rgba(0, 0, 0, 0.3)">mdi-food-variant</v-icon>
          <p class="empty-text">検索して食材を追加してください</p>
        </div>
        <div v-else class="ingredients-grid">
          <IngredientTag
            v-for="ingredient in selectedIngredients"
            :key="ingredient.id"
            :ingredient="ingredient"
            @remove="removeIngredient"
          />
        </div>
      </div>
    </div>

    <!-- レシピを探すボタン -->
    <div class="action-section">
      <v-btn
        color="primary"
        size="large"
        class="search-recipes-btn"
        block
        @click="searchRecipes"
      >
        <v-icon left>mdi-chef-hat</v-icon>
        <span v-if="selectedCount > 0">
          レシピを探す ({{ selectedCount }}品目)
        </span>
        <span v-else> 全レシピを見る </span>
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useIngredientsStore } from '~/stores/ingredients'
import IngredientSearchInput from '~/components/IngredientSearchInput.vue'
import IngredientTag from '~/components/IngredientTag.vue'

const ingredientsStore = useIngredientsStore()
const { selectedIngredients, selectedCount, hasSelectedIngredients } =
  storeToRefs(ingredientsStore)

const removeIngredient = (ingredientId: number) => {
  ingredientsStore.removeIngredient(ingredientId)
}

const searchRecipes = async () => {
  // レシピ検索ページに遷移（食材選択の有無に関わらず）
  await navigateTo('/recipes')
}

// SEOとメタデータ
useHead({
  title: '食材登録 - Reliche',
  meta: [
    {
      name: 'description',
      content: '手持ちの食材を登録して、おすすめレシピを見つけよう',
    },
  ],
})
</script>

<style scoped>
.ingredient-registration {
  min-height: 100vh;
  background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e8 100%);
  padding: 16px;
  padding-bottom: calc(88px + env(safe-area-inset-bottom));
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 0 4px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #2e7d32;
  margin: 0;
}

.menu-btn {
  color: #666;
}

.search-section {
  margin-bottom: 32px;
}

.selected-ingredients-section {
  margin-bottom: 32px;
}

.section-title {
  font-size: 18px;
  font-weight: 500;
  color: #333;
  margin-bottom: 16px;
}

.ingredients-container {
  background-color: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  min-height: 120px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  text-align: center;
}

.empty-text {
  margin-top: 12px;
  color: rgba(0, 0, 0, 0.6);
  font-size: 14px;
}

.ingredients-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: -4px;
}

.action-section {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  background-color: white;
  border-top: 1px solid rgba(0, 0, 0, 0.12);
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
  z-index: 9999;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
}

.search-recipes-btn {
  height: 56px;
  font-size: 16px;
  font-weight: 600;
  text-transform: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
}

.search-recipes-btn:disabled {
  background: #e0e0e0 !important;
  color: rgba(0, 0, 0, 0.26) !important;
}

.search-recipes-btn .v-icon {
  margin-right: 8px;
}

/* レスポンシブ対応 */
@media (max-width: 600px) {
  .ingredient-registration {
    padding: 12px;
  }

  .page-title {
    font-size: 20px;
  }

  .action-section {
    padding: 12px;
  }

  .search-recipes-btn {
    height: 48px;
    font-size: 14px;
  }
}

/* iPad対応 */
@media (min-width: 768px) {
  .ingredient-registration {
    max-width: 600px;
    margin: 0 auto;
    padding: 24px;
  }

  .action-section {
    position: static;
    padding: 0;
    background: transparent;
    border: none;
    box-shadow: none;
  }

  .search-recipes-btn {
    max-width: 400px;
    margin: 0 auto;
    display: block;
  }
}
</style>
