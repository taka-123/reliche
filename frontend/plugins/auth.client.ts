export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore()
  
  // クライアントサイドでのみ認証状態を初期化
  if (process.client) {
    await authStore.initAuth()
  }
})