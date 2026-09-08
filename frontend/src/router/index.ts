import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
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
})

export default router
