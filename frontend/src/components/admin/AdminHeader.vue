<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await authStore.logout()
  router.push('/admin/login')
}
</script>

<template>
  <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 dark:border-gray-800 dark:bg-gray-900">
    <div>
      <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Administration</h1>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-sm text-gray-600 dark:text-gray-400">{{ authStore.user?.name }}</span>
      <button
        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 transition-colors hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white"
        @click="handleLogout"
      >
        Logout
      </button>
    </div>
  </header>
</template>
