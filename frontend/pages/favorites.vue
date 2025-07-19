<template>
  <v-container>
    <div class="d-flex align-center justify-space-between mb-6">
      <h1 class="text-h4 font-weight-bold">お気に入りレシピ</h1>
      <v-chip 
        color="primary" 
        variant="tonal"
        size="large"
      >
        {{ favoriteCount }}件
      </v-chip>
    </div>

    <!-- ローディング -->
    <div v-if="loading" class="text-center py-8">
      <v-progress-circular
        indeterminate
        color="primary"
        size="64"
      />
      <p class="mt-4 text-body-1">お気に入りを読み込んでいます...</p>
    </div>

    <!-- エラー -->
    <v-alert
      v-if="error"
      type="error"
      variant="tonal"
      class="mb-6"
      @click:close="clearError"
      closable
    >
      {{ error }}
    </v-alert>

    <!-- 空の状態 -->
    <div v-if="!loading && favoriteRecipes.length === 0" class="text-center py-12">
      <v-icon 
        size="80" 
        color="grey-lighten-1"
        class="mb-4"
      >
        mdi-heart-outline
      </v-icon>
      <h2 class="text-h6 mb-4">お気に入りレシピがありません</h2>
      <p class="text-body-2 text-grey mb-6">
        気に入ったレシピを見つけたら、ハートマークをタップしてお気に入りに追加しましょう！
      </p>
      <v-btn 
        color="primary" 
        size="large" 
        to="/"
        prepend-icon="mdi-magnify"
      >
        レシピを探す
      </v-btn>
    </div>

    <!-- お気に入りレシピ一覧 -->
    <div v-if="!loading && favoriteRecipes.length > 0" class="recipe-grid">
      <v-card
        v-for="recipe in favoriteRecipes"
        :key="recipe.id"
        class="recipe-card mb-4"
        elevation="2"
        hover
        @click="navigateTo(`/recipes/${recipe.id}`)"
      >
        <div class="d-flex">
          <!-- レシピ画像プレースホルダー -->
          <div class="recipe-image flex-shrink-0">
            <v-img
              :src="`https://picsum.photos/200/150?random=${recipe.id}`"
              :alt="recipe.name"
              width="200"
              height="150"
              cover
              class="rounded-l"
            />
          </div>

          <!-- レシピ情報 -->
          <div class="flex-grow-1 pa-4">
            <div class="d-flex justify-space-between align-start mb-2">
              <h3 class="text-h6 font-weight-bold">{{ recipe.name }}</h3>
              <FavoriteButton 
                :recipe-id="recipe.id" 
                :show-toast="false"
              />
            </div>

            <div class="d-flex align-center mb-3">
              <v-icon size="16" class="mr-1">mdi-clock-outline</v-icon>
              <span class="text-body-2">{{ recipe.cooking_time }}分</span>
            </div>

            <!-- 食材リスト -->
            <div class="mb-3">
              <p class="text-body-2 text-grey mb-1">材料:</p>
              <div class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="ingredient in recipe.ingredients.slice(0, 4)"
                  :key="ingredient.id"
                  size="x-small"
                  variant="outlined"
                  color="primary"
                >
                  {{ ingredient.name }}
                </v-chip>
                <v-chip
                  v-if="recipe.ingredients.length > 4"
                  size="x-small"
                  variant="outlined"
                  color="grey"
                >
                  他{{ recipe.ingredients.length - 4 }}品
                </v-chip>
              </div>
            </div>

            <!-- アクション -->
            <div class="d-flex justify-end">
              <v-btn 
                color="primary"
                variant="outlined"
                size="small"
                @click.stop="navigateTo(`/recipes/${recipe.id}`)"
              >
                レシピを見る
              </v-btn>
            </div>
          </div>
        </div>
      </v-card>
    </div>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useFavoritesStore } from '~/stores/favorites'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  middleware: 'auth' // 認証が必要
})

const favoritesStore = useFavoritesStore()
const authStore = useAuthStore()

// データ
const favoriteRecipes = computed(() => favoritesStore.getFavoriteRecipes)
const favoriteCount = computed(() => favoritesStore.getFavoriteCount)
const loading = computed(() => favoritesStore.loading)
const error = computed(() => favoritesStore.error)

// エラークリア
const clearError = () => {
  favoritesStore.clearError()
}

// 初期データ読み込み
onMounted(async () => {
  await favoritesStore.fetchFavorites()
})

// SEO
useHead({
  title: 'お気に入りレシピ - Reliche',
  meta: [
    {
      name: 'description',
      content: 'あなたがお気に入りに登録したレシピ一覧です。'
    }
  ]
})
</script>

<style scoped>
.recipe-grid {
  max-width: 900px;
  margin: 0 auto;
}

.recipe-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.recipe-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.recipe-image {
  position: relative;
}

.recipe-image::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, rgba(76, 175, 80, 0.1), rgba(46, 125, 50, 0.1));
  pointer-events: none;
}

@media (max-width: 600px) {
  .recipe-card .d-flex {
    flex-direction: column;
  }
  
  .recipe-image {
    width: 100% !important;
  }
  
  .recipe-image :deep(.v-img) {
    width: 100% !important;
    border-radius: 4px 4px 0 0 !important;
  }
}
</style>