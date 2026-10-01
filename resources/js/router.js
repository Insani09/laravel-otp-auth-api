import { createRouter, createWebHistory } from 'vue-router'
import Login from './pages/auth/Login.vue'
import Register from './pages/auth/Register.vue'
import Dashboard from './pages/Dashboard.vue'
import Profile from './pages/Profile.vue'
import adminRoutes from './pages/admin/index.js'
import { fetchUser, isAdmin, user } from './auth'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: Login, meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: Register, meta: { guestOnly: true } },
  { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/profile', name: 'profile', component: Profile, meta: { requiresAuth: true } },
  ...adminRoutes,
  // Kompatibilitas link lama /vue/* → root
  { path: '/vue/:pathMatch(.*)*', redirect: (to) => '/' + (to.params.pathMatch || '') },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

let userPromise = null

async function ensureUser() {
  if (user.value) {
    return 'ok'
  }
  if (!userPromise) {
    userPromise = fetchUser().finally(() => {
      userPromise = null
    })
  }
  return userPromise
}

router.beforeEach(async (to) => {
  const status = await ensureUser()

  // Server/network error: jangan blokir navigasi; halaman menyesuaikan diri.
  if (status === 'error') {
    return true
  }

  if (to.meta.requiresAuth && !user.value) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.adminOnly && !isAdmin()) {
    return user.value ? { path: '/dashboard' } : { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && user.value) {
    return isAdmin() ? { path: '/admin' } : { path: '/dashboard' }
  }

  return true
})

export default router
