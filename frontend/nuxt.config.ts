// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@pinia/nuxt'],

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    // Server-only base URL for SSR requests. In Docker this points at the
    // backend service (http://backend:8000/api). Set via NUXT_API_BASE_SERVER.
    // When empty it falls back to public.apiBase.
    apiBaseServer: '',
    public: {
      // Used by the browser. Override in production via NUXT_PUBLIC_API_BASE.
      apiBase: 'http://localhost:8000/api',
    },
  },

  app: {
    head: {
      title: 'Список задач',
      htmlAttrs: { lang: 'ru' },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Список задач на Nuxt 3 и Laravel.' },
      ],
    },
  },
})
