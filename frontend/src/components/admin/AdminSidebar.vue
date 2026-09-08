<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()

const navItems = [
  { to: '/admin', label: 'Dashboard', icon: 'grid' },
  { to: '/admin/nodes', label: 'Nodes', icon: 'server' },
  { to: '/admin/tests', label: 'Tests', icon: 'terminal' },
  { to: '/admin/downloads', label: 'Downloads', icon: 'download' },
  { to: '/admin/settings', label: 'Settings', icon: 'sliders' },
  { to: '/admin/users', label: 'Users', icon: 'users' },
  { to: '/admin/security', label: 'Security', icon: 'shield' },
  { to: '/admin/system', label: 'System', icon: 'cpu' },
]

function isActive(to: string): boolean {
  if (to === '/admin') return route.path === '/admin'
  return route.path.startsWith(to)
}
</script>

<template>
  <aside class="flex w-60 flex-col border-r border-gray-800 bg-gray-900">
    <!-- Logo -->
    <div class="flex h-16 items-center border-b border-gray-800 px-4">
      <router-link to="/admin" class="flex items-center gap-2 text-white">
        <span class="text-lg font-bold">LG Admin</span>
      </router-link>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1 px-3 py-4">
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
        :class="isActive(item.to)
          ? 'bg-primary-600/20 text-primary-400'
          : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
      >
        <span>{{ item.label }}</span>
      </router-link>
    </nav>

    <!-- Back to Site -->
    <div class="border-t border-gray-800 p-3">
      <router-link
        to="/"
        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-500 transition-colors hover:text-white"
      >
        Back to Site
      </router-link>
    </div>
  </aside>
</template>
