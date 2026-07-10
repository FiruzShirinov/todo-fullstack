import type { $Fetch } from 'nitropack'

/**
 * A pre-configured $fetch instance that:
 *  - points at the Laravel API base URL,
 *  - attaches the Sanctum bearer token from the auth cookie,
 *  - on a 401 response, clears the token and redirects to the login page.
 */
export function useApi(): $Fetch {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('auth_token')

  // On the server (SSR) prefer the internal base URL when configured;
  // the browser always uses the public one.
  const baseURL = import.meta.server
    ? config.apiBaseServer || config.public.apiBase
    : config.public.apiBase

  return $fetch.create({
    baseURL,
    headers: { Accept: 'application/json' },

    onRequest({ options }) {
      if (token.value) {
        const headers = new Headers(options.headers as HeadersInit)
        headers.set('Authorization', `Bearer ${token.value}`)
        options.headers = headers
      }
    },

    onResponseError({ response }) {
      if (response.status === 401) {
        token.value = null
        // Interceptors can run outside of a component setup context, so we
        // avoid useRoute() here and read the path from the browser location.
        if (import.meta.client && window.location.pathname !== '/login') {
          navigateTo('/login')
        }
      }
    },
  })
}
