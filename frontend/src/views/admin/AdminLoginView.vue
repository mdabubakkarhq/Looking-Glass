<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')

async function handleLogin() {
  const ok = await authStore.login(email.value, password.value)
  if (ok) {
    router.push('/admin')
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-950 px-4">
    <div class="w-full max-w-md">
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-white">Open Looking Glass</h1>
        <p class="mt-2 text-gray-400">Admin Panel Login</p>
      </div>

      <form
        @submit.prevent="handleLogin"
        class="space-y-5 rounded-lg border border-gray-800 bg-gray-900 p-6"
      >
        <div
          v-if="authStore.error"
          class="rounded-lg border border-red-800 bg-red-900/30 px-4 py-3 text-sm text-red-400"
        >
          {{ authStore.error }}
        </div>

        <div>
          <label for="email" class="mb-1.5 block text-sm font-medium text-gray-300">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="admin@example.com"
          />
        </div>

        <div>
          <label for="password" class="mb-1.5 block text-sm font-medium text-gray-300">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="Enter password"
          />
        </div>

        <button
          type="submit"
          :disabled="authStore.loading"
          class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {{ authStore.loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>
    </div>
  </div>
</template>
