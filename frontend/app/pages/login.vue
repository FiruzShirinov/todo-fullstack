<script setup lang="ts">
import { reactive, ref } from 'vue'
import { validateLoginForm, isFormValid } from '~/utils/validation'

definePageMeta({
  middleware: 'guest',
  layout: 'blank',
})

useHead({ title: 'Вход · Задачи' })

const auth = useAuthStore()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const errors = ref<Record<string, string | undefined>>({})
const formError = ref('')
const submitting = ref(false)

const DEMO_PASSWORD = 'password'
const demoAccounts = [
  { role: 'Админ', email: 'admin@example.com' },
  { role: 'Пользователь', email: 'user@example.com' },
  { role: 'Наблюдатель', email: 'viewer@example.com' },
]

const copiedKey = ref('')
let copiedTimer: ReturnType<typeof setTimeout> | undefined

async function copyText(key: string, text: string) {
  try {
    await navigator.clipboard.writeText(text)
    copiedKey.value = key
    clearTimeout(copiedTimer)
    copiedTimer = setTimeout(() => {
      copiedKey.value = ''
    }, 1200)
  } catch {
    // Clipboard API unavailable (e.g. insecure context) — fail silently.
  }
}

async function onSubmit() {
  formError.value = ''
  const validation = validateLoginForm(form)
  errors.value = validation
  if (!isFormValid(validation)) return

  submitting.value = true
  try {
    await auth.login(form.email.trim(), form.password)
    const redirect = (route.query.redirect as string) || '/'
    await navigateTo(redirect)
  } catch (e: unknown) {
    const err = e as { status?: number; data?: { message?: string } }
    formError.value =
      err?.status === 422 || err?.status === 401
        ? 'Неверный email или пароль.'
        : err?.data?.message || 'Что-то пошло не так. Попробуйте ещё раз.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="login">
    <div class="login__card card">
      <h1 class="login__title">
        С возвращением
      </h1>
      <p class="login__subtitle">
        Войдите, чтобы управлять задачами.
      </p>

      <div
        v-if="formError"
        class="alert alert--error"
        role="alert"
      >
        {{ formError }}
      </div>

      <form
        novalidate
        @submit.prevent="onSubmit"
      >
        <div class="field">
          <label
            class="field__label"
            for="email"
          >Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="username"
            class="field__input"
            :class="{ 'field__input--error': errors.email }"
            placeholder="you@example.com"
          >
          <span
            v-if="errors.email"
            class="field__error"
          >{{ errors.email }}</span>
        </div>

        <div class="field">
          <label
            class="field__label"
            for="password"
          >Пароль</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            class="field__input"
            :class="{ 'field__input--error': errors.password }"
            placeholder="••••••••"
          >
          <span
            v-if="errors.password"
            class="field__error"
          >{{ errors.password }}</span>
        </div>

        <button
          type="submit"
          class="btn btn--primary login__submit"
          :disabled="submitting"
        >
          <span
            v-if="submitting"
            class="spinner"
            style="width: 15px; height: 15px"
          />
          {{ submitting ? 'Вход…' : 'Войти' }}
        </button>
      </form>

      <div class="login__hint">
        <strong>Тестовые аккаунты</strong>
        <span class="login__hint-note">нажмите, чтобы скопировать</span>

        <div
          v-for="account in demoAccounts"
          :key="account.email"
          class="login__account"
        >
          <span class="login__account-role">{{ account.role }}:</span>
          <button
            type="button"
            class="login__copyable"
            @click="copyText(account.email, account.email)"
          >
            {{ account.email }}
            <span
              v-if="copiedKey === account.email"
              class="login__copied"
            >Скопировано!</span>
          </button>
        </div>

        <div class="login__account">
          <span class="login__account-role">Пароль:</span>
          <button
            type="button"
            class="login__copyable"
            @click="copyText('password', DEMO_PASSWORD)"
          >
            <code>{{ DEMO_PASSWORD }}</code>
            <span
              v-if="copiedKey === 'password'"
              class="login__copied"
            >Скопировано!</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login {
  display: flex;
  justify-content: center;
}

.login__card {
  width: 100%;
  max-width: 400px;
  padding: 2rem;
}

.login__title {
  margin: 0 0 0.25rem;
  font-size: 1.5rem;
}

.login__subtitle {
  margin: 0 0 1.5rem;
  color: var(--muted);
  font-size: 0.9rem;
}

.login__submit {
  width: 100%;
  margin-top: 0.5rem;
}

.login__hint {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--border);
  font-size: 0.8rem;
  color: var(--muted);
}

.login__hint-note {
  font-size: 0.72rem;
  opacity: 0.75;
  margin-top: -0.15rem;
}

.login__account {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.login__account-role {
  flex-shrink: 0;
  min-width: 6.5rem;
}

.login__copyable {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: none;
  background: none;
  padding: 0.1rem 0.35rem;
  margin: -0.1rem -0.35rem;
  border-radius: 6px;
  font: inherit;
  color: var(--text);
  cursor: pointer;
  text-align: left;
}

.login__copyable:hover {
  background: var(--bg);
}

.login__copyable code {
  background: var(--bg);
  padding: 0.05rem 0.3rem;
  border-radius: 4px;
}

.login__copied {
  color: var(--success);
  font-weight: 600;
  font-size: 0.72rem;
}
</style>
