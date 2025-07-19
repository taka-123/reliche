export default defineNuxtRouteMiddleware((to, from) => {
  const { $pinia } = useNuxtApp()
  const authStore = useAuthStore($pinia)

  // 認証されていないユーザーはログインページにリダイレクト
  if (!authStore.isAuthenticated) {
    return navigateTo('/login')
  }
})