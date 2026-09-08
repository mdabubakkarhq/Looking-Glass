import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminUser } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AdminUser | null>(adminApi.getStoredUser())
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => adminApi.isAuthenticated() && user.value !== null)

  async function login(email: string, password: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const result = await adminApi.login({ email, password, device_name: 'admin-panel' })
      user.value = result.data.user
      return true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Login failed'
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    await adminApi.logout()
    user.value = null
  }

  async function checkAuth(): Promise<boolean> {
    if (!adminApi.isAuthenticated()) {
      user.value = null
      return false
    }

    try {
      const result = await adminApi.me()
      user.value = result.data
      return true
    } catch {
      user.value = null
      return false
    }
  }

  return { user, loading, error, isAuthenticated, login, logout, checkAuth }
})
