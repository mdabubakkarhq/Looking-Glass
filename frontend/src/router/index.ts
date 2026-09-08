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
      path: '/downloads',
      name: 'downloads',
      component: () => import('@/views/DownloadsView.vue'),
      meta: { title: 'Download Speed Tests' },
    },
    {
      path: '/status',
      name: 'status',
      component: () => import('@/views/StatusView.vue'),
      meta: { title: 'System Status' },
    },

    // ── Admin Login ──────────────────────────────────────────
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/views/admin/AdminLoginView.vue'),
      meta: { title: 'Admin Login' },
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
          path: 'security',
          name: 'admin-security',
          component: () => import('@/views/admin/AdminSecurityView.vue'),
          meta: { title: 'Security Events' },
        },
        {
          path: 'system',
          name: 'admin-system',
          component: () => import('@/views/admin/AdminSystemView.vue'),
          meta: { title: 'System' },
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
})

export default router
