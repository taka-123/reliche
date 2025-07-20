export default defineNuxtRouteMiddleware((_to, _from) => {
  const { isAuthenticated } = useAuthStore()

  if (!isAuthenticated) {
    return navigateTo('/login')
  }
})
