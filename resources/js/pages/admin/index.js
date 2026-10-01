import AdminDashboard from './AdminDashboard.vue'

export default [
  {
    path: '/admin',
    name: 'admin.dashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, adminOnly: true },
  },
]
