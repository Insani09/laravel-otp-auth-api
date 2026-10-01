import { ref } from 'vue'

const TOKEN_KEY = 'auth_token'

export const user = ref(null)
export const sessionReady = ref(false)

export function getToken() {
  return localStorage.getItem(TOKEN_KEY) || ''
}

export function setSession(token, userData) {
  localStorage.setItem(TOKEN_KEY, token)
  user.value = userData || null
}

export function setUser(userData) {
  user.value = userData || null
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY)
  user.value = null
}

export function isAdmin() {
  return user.value?.role === 'admin'
}

/**
 * Ambil data user untuk guard vue-router.
 * Urutan: Bearer token (/api/user) → fallback session cookie (/api/me).
 * Returns: 'ok' | 'guest' (tidak ada sesi) | 'error'
 */
export async function fetchUser() {
  const token = getToken()

  if (token) {
    try {
      const response = await window.axios.get('/api/user', {
        headers: { Authorization: `Bearer ${token}` },
      })
      user.value = response.data.user || null
      sessionReady.value = true
      return user.value ? 'ok' : 'guest'
    } catch (error) {
      if (error?.response?.status === 401) {
        // Token invalid/kadaluarsa — bersihkan, lalu coba fallback session.
        clearSession()
      } else {
        // Error jaringan/server: jangan hapus token, biarkan user retry.
        sessionReady.value = true
        return 'error'
      }
    }
  }

  // Tanpa token: coba session cookie (statefulApi aktif di sisi Laravel).
  try {
    const response = await window.axios.get('/api/me')
    user.value = response.data.user || null
    sessionReady.value = true
    return user.value ? 'ok' : 'guest'
  } catch {
    user.value = null
    sessionReady.value = true
    return 'guest'
  }
}

export function authHeaders() {
  const token = getToken()
  return token ? { Authorization: `Bearer ${token}` } : {}
}
