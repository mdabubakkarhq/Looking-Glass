<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { adminApi } from '@/api/admin'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const mode = ref<'login' | 'forgot' | 'reset-sent'>('login')
const forgotEmail = ref('')
const forgotLoading = ref(false)
const forgotMsg = ref('')
const forgotError = ref('')

async function handleLogin() {
  const ok = await authStore.login(email.value, password.value)
  if (ok) {
    router.push('/admin')
  }
}

async function handleForgotPassword() {
  forgotLoading.value = true
  forgotError.value = ''
  forgotMsg.value = ''
  try {
    const res = await adminApi.forgotPassword(forgotEmail.value)
    forgotMsg.value = res.message
    mode.value = 'reset-sent'
  } catch (err) {
    forgotError.value = err instanceof Error ? err.message : 'Failed to send reset link'
  } finally {
    forgotLoading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 dark:bg-gray-950">
    <div class="w-full max-w-md">
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Open Looking Glass</h1>
        <p class="mt-2 text-gray-500 dark:text-gray-400">{{ mode === 'login' ? 'Admin Panel Login' : 'Reset Password' }}</p>
      </div>

      <!-- Login Form -->
      <form
        v-if="mode === 'login'"
        @submit.prevent="handleLogin"
        class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
      >
        <div v-if="authStore.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
          {{ authStore.error }}
        </div>

        <div>
          <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
          <input id="email" v-model="email" type="email" required autocomplete="email"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="admin@example.com" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <button type="button" class="text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400" @click="mode = 'forgot'">
              Forgot password?
            </button>
          </div>
          <input id="password" v-model="password" type="password" required autocomplete="current-password"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="Enter password" />
        </div>

        <button type="submit" :disabled="authStore.loading"
          class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50">
          {{ authStore.loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <!-- Forgot Password Form -->
      <form v-if="mode === 'forgot'" @submit.prevent="handleForgotPassword"
        class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <p class="text-sm text-gray-500 dark:text-gray-400">Enter your email address and we'll send you a link to reset your password.</p>

        <div v-if="forgotError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
          {{ forgotError }}
        </div>

        <div>
          <label for="forgot-email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
          <input id="forgot-email" v-model="forgotEmail" type="email" required autocomplete="email"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            placeholder="admin@example.com" />
        </div>

        <button type="submit" :disabled="forgotLoading"
          class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50">
          {{ forgotLoading ? 'Sending...' : 'Send Reset Link' }}
        </button>

        <div class="text-center">
          <button type="button" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400" @click="mode = 'login'">
            ← Back to Sign In
          </button>
        </div>
      </form>

      <!-- Reset Sent Confirmation -->
      <div v-if="mode === 'reset-sent'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
          <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ forgotMsg }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">If the email exists in our system, you will receive a password reset link shortly.</p>
        <button class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400" @click="mode = 'login'">
          ← Back to Sign In
        </button>
      </div>
    </div>
  </div>
</template>
