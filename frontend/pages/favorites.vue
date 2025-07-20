<template>
  <div class="favorites-page">
    <!-- ページヘッダー -->
    <div class="page-header mb-6">
      <v-container>
        <div class="d-flex align-center justify-space-between">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">お気に入りレシピ</h1>
            <p class="text-body-1 text-medium-emphasis">
              保存したレシピを管理できます
            </p>
          </div>
          
          <v-chip
            v-if="favoritesCount > 0"
            color="primary"
            variant="elevated"
            size="large"
          >
            {{ favoritesCount }}件
          </v-chip>
        </div>
      </v-container>
    </div>

    <!-- エラー表示 -->
    <v-container v-if="error">
      <v-alert
        type="error"
        variant="tonal"
        prominent
        border="start"
        class="mb-4"
      >
        <strong>エラーが発生しました</strong><br>
        {{ error }}
        <template #append>
          <v-btn
            color="error"
            variant="text"
            size="small"
            @click="clearError"
          >
            閉じる
          </v-btn>
        </template>
      </v-alert>
    </v-container>

    <!-- ローディング状態 -->
    <v-container v-if="isLoading && favorites.length === 0">
      <div class="d-flex justify-center py-8">
        <v-progress-circular
          indeterminate
          color="primary"
          size="64"
        />
      </div>
    </v-container>

    <!-- お気に入りリスト -->
    <v-container v-else>
      <!-- 空の状態 -->
      <div v-if="favorites.length === 0 && !isLoading" class="empty-state text-center py-12">
        <v-icon
          size="80"
          color="grey-lighten-1"
          class="mb-4"
        >
          mdi-heart-outline
        </v-icon>
        
        <h2 class="text-h5 mb-3">お気に入りレシピがありません</h2>
        <p class="text-body-1 text-medium-emphasis mb-6">
          気になるレシピを見つけたら、ハートボタンでお気に入りに追加しましょう
        </p>
        
        <v-btn
          to="/"
          color="primary"
          variant="elevated"
          size="large"
          prepend-icon="mdi-magnify"
        >
          レシピを探す
        </v-btn>
      </div>

      <!-- お気に入りグリッド -->
      <div v-else class="favorites-grid">
        <v-row>
          <v-col
            v-for="favorite in favorites"
            :key="favorite.id"
            cols="12"
            sm="6"
            md="4"
            lg="3"
          >
            <!-- 将来的にはRecipeCardコンポーネントを使用 -->
            <!-- 現在はシンプルなカード表示 -->
            <v-card 
              class="favorite-card"
              :elevation="2"
              @click="navigateToRecipe(favorite.recipe_id)"
            >
              <v-img
                src="/images/default-recipe.jpg"
                height="200"
                cover
              >
                <!-- お気に入り削除ボタン -->
                <div class="favorite-remove-overlay">
                  <FavoriteButton
                    :recipe-id="favorite.recipe_id"
                    size="small"
                    @favorite-removed="handleFavoriteRemoved"
                    @error="handleError"
                  />
                </div>
              </v-img>

              <v-card-text>
                <h3 class="text-h6 mb-2">
                  レシピ #{{ favorite.recipe_id }}
                </h3>
                <p class="text-caption text-medium-emphasis">
                  追加日: {{ formatDate(favorite.created_at) }}
                </p>
              </v-card-text>

              <v-card-actions>
                <v-btn
                  :to="`/recipes/${favorite.recipe_id}`"
                  color="primary"
                  variant="text"
                  size="small"
                >
                  詳細を見る
                </v-btn>
                
                <v-spacer />
                
                <v-btn
                  color="error"
                  variant="text"
                  size="small"
                  @click.stop="confirmRemoveFavorite(favorite.recipe_id)"
                >
                  削除
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>
      </div>
    </v-container>

    <!-- 削除確認ダイアログ -->
    <v-dialog
      v-model="showRemoveDialog"
      max-width="400"
    >
      <v-card>
        <v-card-title class="text-h6">
          お気に入り削除
        </v-card-title>
        
        <v-card-text>
          このレシピをお気に入りから削除しますか？
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          
          <v-btn
            color="grey"
            variant="text"
            @click="showRemoveDialog = false"
          >
            キャンセル
          </v-btn>
          
          <v-btn
            color="error"
            variant="text"
            :loading="isLoading"
            @click="removeFavorite"
          >
            削除
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- 成功スナックバー -->
    <v-snackbar
      v-model="showSuccessMessage"
      color="success"
      timeout="3000"
    >
      {{ successMessage }}
      
      <template #actions>
        <v-btn
          color="white"
          variant="text"
          @click="showSuccessMessage = false"
        >
          閉じる
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { useFavorites } from '~/composables/useFavorites'

definePageMeta({
  middleware: 'auth', // 認証が必要
  title: 'お気に入りレシピ',
})

// お気に入り機能を使用
const {
  favorites,
  favoritesCount,
  isLoading,
  error,
  fetchFavorites,
  removeFromFavorites,
  clearError,
} = useFavorites()

// ローカル状態
const showRemoveDialog = ref(false)
const removeTargetRecipeId = ref<number | null>(null)
const showSuccessMessage = ref(false)
const successMessage = ref('')

// ページ初期化
onMounted(async () => {
  await fetchFavorites()
})

// Methods
const navigateToRecipe = (recipeId: number) => {
  navigateTo(`/recipes/${recipeId}`)
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const confirmRemoveFavorite = (recipeId: number) => {
  removeTargetRecipeId.value = recipeId
  showRemoveDialog.value = true
}

const removeFavorite = async () => {
  if (!removeTargetRecipeId.value) return

  const success = await removeFromFavorites(removeTargetRecipeId.value)
  
  if (success) {
    successMessage.value = 'お気に入りから削除しました'
    showSuccessMessage.value = true
  }
  
  showRemoveDialog.value = false
  removeTargetRecipeId.value = null
}

const handleFavoriteRemoved = (recipeId: number) => {
  successMessage.value = 'お気に入りから削除しました'
  showSuccessMessage.value = true
}

const handleError = (message: string) => {
  // エラーは useFavorites の error state で管理される
  console.error('お気に入り操作エラー:', message)
}

// SEO設定
useHead({
  title: 'お気に入りレシピ - Reliche',
  meta: [
    {
      name: 'description',
      content: 'お気に入りに保存したレシピを管理・閲覧できます。',
    },
  ],
})
</script>

<style scoped>
.favorites-page {
  min-height: 100vh;
  background: rgb(var(--v-theme-background));
}

.page-header {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-secondary)) 100%);
  color: white;
  padding: 2rem 0;
}

.favorites-grid {
  margin-top: 2rem;
}

.favorite-card {
  transition: all 0.3s ease;
  cursor: pointer;
  border-radius: 16px;
  overflow: hidden;
}

.favorite-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(var(--v-theme-primary), 0.15);
}

.favorite-remove-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 2;
}

.empty-state {
  padding: 4rem 2rem;
}

/* ローディング状態のスタイル */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* レスポンシブ対応 */
@media (max-width: 600px) {
  .page-header {
    padding: 1.5rem 0;
  }
  
  .page-header h1 {
    font-size: 1.5rem !important;
  }
  
  .empty-state {
    padding: 3rem 1rem;
  }
}
</style>