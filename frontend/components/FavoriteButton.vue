<template>
  <v-btn
    :icon="isFavorited ? 'mdi-heart' : 'mdi-heart-outline'"
    :color="isFavorited ? 'red' : 'grey'"
    :loading="loading"
    :disabled="loading || !isAuthenticated"
    size="large"
    variant="text"
    @click="toggleFavorite"
  >
    <v-icon size="24">
      {{ isFavorited ? 'mdi-heart' : 'mdi-heart-outline' }}
    </v-icon>
    
    <!-- ツールチップ -->
    <v-tooltip activator="parent" location="bottom">
      <span v-if="!isAuthenticated">
        ログインが必要です
      </span>
      <span v-else-if="isFavorited">
        お気に入りから削除
      </span>
      <span v-else>
        お気に入りに追加
      </span>
    </v-tooltip>
  </v-btn>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useFavoritesStore } from '~/stores/favorites'
import { useAuthStore } from '~/stores/auth'

interface Props {
  recipeId: number
  showToast?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showToast: true
})

const favoritesStore = useFavoritesStore()
const authStore = useAuthStore()

const loading = ref(false)

// 認証状態
const isAuthenticated = computed(() => authStore.isAuthenticated)

// お気に入り状態
const isFavorited = computed(() => favoritesStore.isFavorited(props.recipeId))

// お気に入り状態の切り替え
const toggleFavorite = async () => {
  if (!isAuthenticated.value) {
    // 認証されていない場合はログインページに移動
    await navigateTo('/login')
    return
  }

  loading.value = true

  try {
    const result = await favoritesStore.toggleFavorite(props.recipeId)
    
    if (result.success && props.showToast) {
      // 成功時のトースト通知
      const { $toast } = useNuxtApp()
      if ($toast) {
        $toast.success(result.message)
      }
    } else if (!result.success) {
      // エラー時のトースト通知
      const { $toast } = useNuxtApp()
      if ($toast) {
        $toast.error(result.message)
      }
    }
  } catch (error) {
    console.error('Favorite toggle error:', error)
    const { $toast } = useNuxtApp()
    if ($toast) {
      $toast.error('お気に入りの更新に失敗しました')
    }
  } finally {
    loading.value = false
  }
}

// コンポーネント初期化時にお気に入り状態をチェック
onMounted(async () => {
  if (isAuthenticated.value) {
    await favoritesStore.checkFavoriteStatus(props.recipeId)
  }
})
</script>

<style scoped>
.v-btn {
  transition: all 0.3s ease;
}

.v-btn:hover {
  transform: scale(1.1);
}
</style>