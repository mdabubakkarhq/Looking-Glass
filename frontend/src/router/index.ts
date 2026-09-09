import { createRouter, createWebHistory } from 'vue-router'
import { adminApi } from '@/api/admin'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ── Public Routes ────────────────────────────────────────
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { title: 'Looking Glass' },
    },
    {
      path: '/locations',
      name: 'locations',
      component: () => import('@/views/LocationsView.vue'),
      meta: { title: 'Locations' },
    },
    {
      path: '/network',
      name: 'network',
      component: () => import('@/views/NetworkView.vue'),
      meta: { title: 'Network Information' },
    },
    {
      path: '/status',
      name: 'status',
      component: () => import('@/views/StatusView.vue'),
      meta: { title: 'System Status' },
    },
    {
      path: '/compare',
      name: 'compare',
      component: () => import('@/views/CompareView.vue'),
      meta: { title: 'Multi-Location Comparison' },
    },
    {
      path: '/peering',
      name: 'peering',
      component: () => import('@/views/PeeringView.vue'),
      meta: { title: 'Peering Information' },
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('@/views/AboutView.vue'),
      meta: { title: 'About' },
    },
    {
      path: '/downloads',
      name: 'downloads',
      component: () => import('@/views/DownloadsView.vue'),
      meta: { title: 'Downloads' },
    },

    // ── Admin Login ──────────────────────────────────────────
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/admin/AdminLoginView.vue'),
      meta: { title: 'Admin Login' },
    },
    {
      path: '/admin/reset-password',
      name: 'admin-reset-password',
      component: () => import('@/views/admin/AdminResetPasswordView.vue'),
      meta: { title: 'Reset Password' },
    },

    // ── Admin Routes (require auth) ──────────────────────────
    {
      path: '/admin',
      component: () => import('@/components/admin/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/AdminDashboardView.vue'),
          meta: { title: 'Admin Dashboard' },
        },
        {
          path: 'nodes',
          name: 'admin-nodes',
          component: () => import('@/views/admin/AdminNodesView.vue'),
          meta: { title: 'Node Management' },
        },
        {
          path: 'tests',
          name: 'admin-tests',
          component: () => import('@/views/admin/AdminTestsView.vue'),
          meta: { title: 'Test History' },
        },
        {
          path: 'downloads',
          name: 'admin-downloads',
          component: () => import('@/views/admin/AdminDownloadsView.vue'),
          meta: { title: 'Downloads' },
        },
        {
          path: 'settings',
          name: 'admin-settings',
          component: () => import('@/views/admin/AdminSettingsView.vue'),
          meta: { title: 'Settings' },
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/AdminUsersView.vue'),
          meta: { title: 'Users' },
        },
        {
          path: 'system',
          name: 'admin-system',
          component: () => import('@/views/admin/AdminSystemView.vue'),
          meta: { title: 'System' },
        },
        {
          path: 'logs',
          name: 'admin-logs',
          component: () => import('@/views/admin/AdminLogsView.vue'),
          meta: { title: 'Logs' },
        },
      ],
    },

    // ── Catch-all ────────────────────────────────────────────
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const title = to.meta.title as string | undefined
  document.title = title ? `${title} — Open Looking Glass` : 'Open Looking Glass'

  if (to.meta.requiresAuth && !adminApi.isAuthenticated()) {
    return { name: 'admin-login' }
  }
  if (to.name === 'admin-login' && adminApi.isAuthenticated()) {
    return { name: 'admin-dashboard' }
  }
  // Allow reset-password page even when not authenticated
  if (to.name === 'admin-reset-password') {
    return
  }
})

export default router
