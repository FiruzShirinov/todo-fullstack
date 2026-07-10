import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { User } from '~/types'

export const useAuthStore = defineStore('auth', () => {
  // Token lives in a cookie so it survives reloads and is available during SSR.
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 30,
    sameSite: 'lax',
  })

  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isViewer = computed(() => user.value?.role === 'viewer')
  // Admins and viewers both see every user's tasks (only admins can edit/delete them).
  const canViewAll = computed(() => isAdmin.value || isViewer.value)

  async function login(email: string, password: string): Promise<void> {
    const api = useApi()
    const res = await api<{ token: string; user: User }>('/auth/login', {
      method: 'POST',
      body: { email, password, device_name: 'nuxt-web' },
    })
    token.value = res.token
    user.value = res.user
  }

  /** Load the current user from the API using the stored token. */
  async function fetchUser(): Promise<void> {
    if (!token.value) return
    const api = useApi()
    try {
      const res = await api<{ data: User }>('/user')
      user.value = res.data
    } catch {
      token.value = null
      user.value = null
    }
  }

  async function logout(): Promise<void> {
    const api = useApi()
    try {
      await api('/auth/logout', { method: 'POST' })
    } catch {
      // Ignore network/401 errors — we clear local state regardless.
    }
    token.value = null
    user.value = null
    await navigateTo('/login')
  }

  return { token, user, isAuthenticated, isAdmin, isViewer, canViewAll, login, fetchUser, logout }
})
