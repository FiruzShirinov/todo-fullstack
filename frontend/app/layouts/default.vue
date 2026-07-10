<script setup lang="ts">
import { computed } from 'vue'

const auth = useAuthStore()

const roleLabel = computed(() => {
  if (auth.isAdmin) return 'Админ'
  if (auth.isViewer) return 'Наблюдатель'
  return 'Пользователь'
})

const roleClass = computed(() => {
  if (auth.isAdmin) return 'badge--admin'
  if (auth.isViewer) return 'badge--viewer'
  return 'badge--user'
})

async function onLogout() {
  await auth.logout()
}
</script>

<template>
  <div class="app-shell">
    <header class="app-header">
      <div class="container app-header__inner">
        <NuxtLink
          to="/"
          class="app-header__brand"
        >
          <span
            class="app-header__logo"
            aria-hidden="true"
          >✓</span>
          <span>Задачи</span>
        </NuxtLink>

        <div
          v-if="auth.isAuthenticated"
          class="app-header__user"
        >
          <div class="app-header__identity">
            <span class="app-header__name">{{ auth.user?.name ?? 'Account' }}</span>
            <span
              v-if="auth.user"
              class="badge"
              :class="roleClass"
            >{{ roleLabel }}</span>
          </div>
          <button
            type="button"
            class="btn btn--ghost"
            @click="onLogout"
          >
            Выйти
          </button>
        </div>
      </div>
    </header>

    <main class="app-main">
      <div class="container">
        <slot />
      </div>
    </main>

    <footer class="app-footer">
      <div class="container">
        <span>Nuxt 3 + Laravel · Тестовое задание</span>
      </div>
    </footer>
  </div>
</template>
