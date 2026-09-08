import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/client'
import type { AppConfig } from '@/types'

export const useAppStore = defineStore('app', () => {
  const config = ref<AppConfig | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const initialized = ref(false)

  async function loadConfig(): Promise<void> {
    if (initialized.value) return

    loading.value = true
    error.value = null

    try {
      config.value = await api.getConfig()
      initialized.value = true
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load configuration'
      console.error('Failed to load config:', err)
    } finally {
      loading.value = false
    }
  }

  function getSiteName(): string {
    return config.value?.site_name ?? 'Open Looking Glass'
  }

  function getOrganization(): string {
    return config.value?.organization ?? ''
  }

  function getTestTypes(): string[] {
    return config.value?.test_types ?? ['ping', 'traceroute', 'mtr', 'dns']
  }

  function getIpFamilies(): string[] {
    return config.value?.ip_families ?? ['auto', 'ipv4', 'ipv6']
  }

  return {
    config,
    loading,
    error,
    initialized,
    loadConfig,
    getSiteName,
    getOrganization,
    getTestTypes,
    getIpFamilies,
  }
})
