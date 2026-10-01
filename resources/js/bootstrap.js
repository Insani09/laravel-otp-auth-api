import axios from 'axios'

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common.Accept = 'application/json'
window.axios.defaults.withCredentials = true

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (csrfToken) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken
}

// Lampirkan Bearer token Sanctum otomatis untuk semua request /api/*.
const TOKEN_KEY = 'auth_token'
window.axios.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  const url = config.url || ''
  const isApi = url.startsWith('/api/') || url.startsWith(`${window.location.origin}/api/`)

  if (token && isApi && !config.headers.Authorization) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

// Sesi API berakhir (token invalid): bersihkan sesi lokal dan arahkan ke login.
let redirectingToLogin = false
window.axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    const url = error?.config?.url || ''

    if (status === 401 && url.startsWith('/api/') && !url.includes('/api/me')) {
      // /api/me adalah probe sesi (dibolehkan 401 tanpa redirect paksa).
      localStorage.removeItem(TOKEN_KEY)
      window.dispatchEvent(new CustomEvent('auth:expired'))

      if (!redirectingToLogin && window.location.pathname !== '/login') {
        redirectingToLogin = true
        window.location.assign('/login')
      }
    }

    return Promise.reject(error)
  },
)
