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
  <header class="flex h-16 items-center justify-between border-b border-gray-800 bg-gray-900 px-6">
    <div>
      <h1 class="text-lg font-semibold text-white">Administration</h1>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-sm text-gray-400">{{ authStore.user?.name }}</span>
      <button
        class="rounded-lg border border-gray-700 px-3 py-1.5 text-sm text-gray-300 transition-colors hover:border-gray-600 hover:text-white"
        @click="handleLogout"
      >
        Logout
      </button>
    </div>
  </header>
</template>
