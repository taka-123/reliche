<template>
  <v-card
    :elevation="hover ? 8 : 2"
    :class="{ 'recipe-card--hover': hover }"
    class="recipe-card"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
  >
    <!-- レシピ画像 -->
    <v-img
      :src="recipe.image_url || '/images/recipe-placeholder.svg'"
      :alt="recipe.title || recipe.name"
      height="200"
      cover
      class="recipe-image"
    >
      <!-- お気に入りボタン（画像の右上） -->
      <div class="favorite-overlay">
        <FavoriteButton
          :recipe-id="recipe.id"
          size="small"
          @favorite-added="handleFavoriteAdded"
          @favorite-removed="handleFavoriteRemoved"
          @error="handleError"
        />
      </div>
    </v-img>

    <!-- レシピ情報 -->
    <v-card-text class="recipe-content">
      <h3 class="recipe-title text-h6 mb-2">
        {{ recipe.title || recipe.name }}
      </h3>

      <p
        v-if="recipe.description"
        class="recipe-description text-body-2 text-medium-emphasis mb-3"
      >
        {{ truncatedDescription }}
      </p>

      <!-- レシピメタ情報 -->
      <div class="recipe-meta d-flex align-center gap-4">
        <div
          v-if="recipe.cooking_time"
          class="meta-item d-flex align-center"
        >
          <v-icon
            size="small"
            class="mr-1"
          >
            mdi-clock-outline
          </v-icon>
          <span class="text-caption">{{ recipe.cooking_time }}分</span>
        </div>

        <div
          v-if="recipe.servings"
          class="meta-item d-flex align-center"
        >
          <v-icon
            size="small"
            class="mr-1"
          >
            mdi-account-group-outline
          </v-icon>
          <span class="text-caption">{{ recipe.servings }}人分</span>
        </div>
      </div>
    </v-card-text>

    <!-- アクションボタン -->
    <v-card-actions class="recipe-actions">
      <v-btn
        :to="`/recipes/${recipe.id}`"
        color="primary"
        variant="outlined"
        size="small"
        block
        class="detail-btn"
      >
        詳細を見る
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Recipe } from '~/types/favorites'

interface Props {
  recipe: Recipe
  maxDescriptionLength?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxDescriptionLength: 100,
})

const emit = defineEmits<{
  favoriteAdded: [recipeId: number]
  favoriteRemoved: [recipeId: number]
  error: [message: string]
}>()

// ローカル状態
const hover = ref(false)

// Computed properties
const truncatedDescription = computed(() => {
  if (!props.recipe.description) return ''

  if (props.recipe.description.length <= props.maxDescriptionLength) {
    return props.recipe.description
  }

  return (
    props.recipe.description.substring(0, props.maxDescriptionLength) + '...'
  )
})

// Event handlers
const handleFavoriteAdded = (recipeId: number) => {
  emit('favoriteAdded', recipeId)
}

const handleFavoriteRemoved = (recipeId: number) => {
  emit('favoriteRemoved', recipeId)
}

const handleError = (message: string) => {
  emit('error', message)
}
</script>

<style scoped>
.recipe-card {
  transition: all 0.3s ease;
  position: relative;
  border-radius: 16px;
  overflow: hidden;
}

.recipe-card--hover {
  transform: translateY(-4px);
}

.recipe-image {
  position: relative;
}

.favorite-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 2;
}

.recipe-content {
  padding: 16px;
}

.recipe-title {
  font-weight: 600;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.recipe-description {
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.recipe-meta {
  gap: 16px;
}

.meta-item {
  color: rgb(var(--v-theme-on-surface-variant));
}

.recipe-actions {
  padding: 8px 16px 16px;
}

/* 詳細を見るボタンのスタイル改善 - お気に入りボタンと統一感を保つ */
.detail-btn {
  font-weight: 500;
  text-transform: none;
  border-radius: 8px;
  border-color: rgba(
    0,
    0,
    0,
    0.12
  ) !important; /* お気に入りボタンと同じ薄いグレー */
  color: #4caf50 !important; /* 緑色のテキスト */
  transition: all 0.3s ease;
}

.detail-btn:hover {
  background-color: rgba(76, 175, 80, 0.08) !important; /* より薄い緑背景 */
  border-color: rgba(76, 175, 80, 0.3) !important; /* ホバー時のみ緑の枠線 */
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15);
}

/* レスポンシブ対応 */
@media (max-width: 600px) {
  .recipe-content {
    padding: 12px;
  }

  .recipe-actions {
    padding: 8px 12px 12px;
  }

  .favorite-overlay {
    top: 4px;
    right: 4px;
  }
}
</style>
