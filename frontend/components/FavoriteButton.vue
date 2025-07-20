<template>
  <v-btn
    :color="isFavorited ? 'error' : 'grey'"
    :variant="isFavorited ? 'flat' : 'outlined'"
    :size="size"
    :disabled="isLoading || disabled"
    :loading="isLoading"
    :aria-label="ariaLabel"
    :aria-pressed="isFavorited"
    role="button"
    class="favorite-btn"
    @click="handleToggle"
  >
    <v-icon 
      :class="{ 'favorite-icon-animated': isAnimating }"
      :size="iconSize"
    >
      {{ isFavorited ? 'mdi-heart' : 'mdi-heart-outline' }}
    </v-icon>
    
    <span v-if="showText" class="ml-2">
      {{ isFavorited ? 'お気に入り解除' : 'お気に入り' }}
    </span>
  </v-btn>
</template>

<script setup lang="ts">
import { useFavorites } from '~/composables/useFavorites'

interface Props {
  recipeId: number
  size?: 'x-small' | 'small' | 'default' | 'large' | 'x-large'
  showText?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'default',
  showText: false,
  disabled: false,
})

const emit = defineEmits<{
  favoriteAdded: [recipeId: number]
  favoriteRemoved: [recipeId: number]
  error: [message: string]
}>()

// お気に入り機能を使用
const { 
  isFavorited: checkIsFavorited, 
  debouncedToggleFavorite, 
  error,
  clearError 
} = useFavorites()

// ローカル状態
const isLoading = ref(false)
const isAnimating = ref(false)

// Computed properties
const isFavorited = computed(() => checkIsFavorited(props.recipeId))

const ariaLabel = computed(() => {
  const action = isFavorited.value ? '解除' : '追加'
  return `レシピをお気に入りに${action}`
})

const iconSize = computed(() => {
  const sizeMap = {
    'x-small': 'small',
    'small': 'small',
    'default': 'default',
    'large': 'large',
    'x-large': 'x-large',
  }
  return sizeMap[props.size] || 'default'
})

// Methods
const handleToggle = async (): Promise<void> => {
  if (isLoading.value || props.disabled) return

  try {
    isLoading.value = true
    clearError()

    // アニメーション開始
    isAnimating.value = true
    setTimeout(() => {
      isAnimating.value = false
    }, 300)

    const wasAlreadyFavorited = isFavorited.value
    const success = await debouncedToggleFavorite(props.recipeId)

    if (success) {
      // 成功時のイベント発火
      if (wasAlreadyFavorited) {
        emit('favoriteRemoved', props.recipeId)
      } else {
        emit('favoriteAdded', props.recipeId)
      }
    } else {
      // エラーハンドリング
      const errorMessage = error.value || 'お気に入りの更新に失敗しました'
      emit('error', errorMessage)
    }
  } catch (err: any) {
    const errorMessage = err.message || 'お気に入りの更新中にエラーが発生しました'
    emit('error', errorMessage)
  } finally {
    isLoading.value = false
  }
}

// エラー監視
watch(error, (newError) => {
  if (newError) {
    emit('error', newError)
  }
})
</script>

<style scoped>
.favorite-btn {
  transition: all 0.3s ease;
}

.favorite-btn:hover {
  transform: scale(1.05);
}

.favorite-icon-animated {
  animation: heartBeat 0.3s ease-in-out;
}

@keyframes heartBeat {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

/* お気に入り状態でのスタイル調整 */
.v-btn--variant-flat.v-btn--color-error {
  background-color: rgb(var(--v-theme-error)) !important;
  color: rgb(var(--v-theme-on-error)) !important;
}

/* ローディング状態のスタイル */
.v-btn--loading {
  pointer-events: none;
}

/* アクセシビリティ対応 */
.favorite-btn:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}
</style>