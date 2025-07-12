import { defineStore } from 'pinia'
import type { RecipeDetail } from '~/types/recipe'

interface WakeLockSentinel {
  release(): Promise<void>
  addEventListener(type: 'release', listener: () => void): void
}

export const useRecipesStore = defineStore('recipes', {
  state: () => ({
    currentRecipe: null as RecipeDetail | null,
    completedSteps: new Set<number>(),
    keepScreenOn: false,
    wakeLock: null as WakeLockSentinel | null
  }),

  getters: {
    isStepCompleted: (state) => (stepIndex: number) => {
      return state.completedSteps.has(stepIndex)
    },
    
    completedStepsCount: (state) => state.completedSteps.size,
    
    totalStepsCount: (state) => {
      if (!state.currentRecipe || !Array.isArray(state.currentRecipe.instructions)) {
        return 0
      }
      return state.currentRecipe.instructions.length
    },
    
    progressPercentage: (state) => {
      if (!state.currentRecipe || !Array.isArray(state.currentRecipe.instructions)) {
        return 0
      }
      const total = state.currentRecipe.instructions.length
      const completed = state.completedSteps.size
      return total > 0 ? Math.round((completed / total) * 100) : 0
    }
  },

  actions: {
    setCurrentRecipe(recipe: RecipeDetail) {
      this.currentRecipe = recipe
      this.completedSteps.clear()
    },

    toggleStep(stepIndex: number) {
      if (this.completedSteps.has(stepIndex)) {
        this.completedSteps.delete(stepIndex)
      } else {
        this.completedSteps.add(stepIndex)
      }
    },

    resetSteps() {
      this.completedSteps.clear()
    },

    async toggleScreenWakeLock() {
      if (!('wakeLock' in navigator)) {
        const config = useRuntimeConfig()
        const isDevelopment = config.public.appEnv === 'development'
        if (isDevelopment) {
          // eslint-disable-next-line no-console
          console.warn('Wake Lock API is not supported')
        }
        return
      }

      try {
        if (this.keepScreenOn) {
          // スリープ防止を解除
          if (this.wakeLock) {
            await this.wakeLock.release()
            this.wakeLock = null
          }
          this.keepScreenOn = false
        } else {
          // スリープ防止を有効化
          this.wakeLock = await navigator.wakeLock.request('screen')
          this.keepScreenOn = true
          
          // Wake Lockが解放された時の処理
          this.wakeLock.addEventListener('release', () => {
            this.keepScreenOn = false
            this.wakeLock = null
          })
        }
      } catch (error) {
        const config = useRuntimeConfig()
        const isDevelopment = config.public.appEnv === 'development'
        if (isDevelopment) {
          // eslint-disable-next-line no-console
          console.error('Wake Lock操作エラー:', error)
        }
      }
    },

    async releaseWakeLock() {
      if (this.wakeLock) {
        await this.wakeLock.release()
        this.wakeLock = null
        this.keepScreenOn = false
      }
    }
  }
})