/**
 * Populate the auth store with the current user on app start
 * (both server and client) when a token cookie is present.
 */
export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  if (auth.isAuthenticated && !auth.user) {
    await auth.fetchUser()
  }
})
