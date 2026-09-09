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

  function getSiteLogo(): string {
    return config.value?.site_logo ?? ''
  }

  function getSiteTitle(): string {
    return config.value?.site_title ?? ''
  }

  function getMetaDescription(): string {
    return config.value?.meta_description ?? ''
  }

  function getOgImage(): string {
    return config.value?.og_image ?? ''
  }

  function getFavicon(): string {
    return config.value?.favicon ?? ''
  }

  function getMenuItems(): import('@/types').MenuItemConfig[] {
    return config.value?.menu_items ?? []
  }

  function getFooterDescription(): string {
    return config.value?.footer_description ?? ''
  }

  function getFooterLinks(): import('@/types').FooterSection[] {
    return config.value?.footer_links?.sections ?? []
  }

  function getCopyrightText(): string {
    const custom = config.value?.copyright_text
    if (custom && custom.trim()) return custom
    const org = getOrganization()
    const name = getSiteName()
    return `© ${new Date().getFullYear()} ${org || name}. All rights reserved.`
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
    getSiteLogo,
    getSiteTitle,
    getMetaDescription,
    getOgImage,
    getFavicon,
    getMenuItems,
    getFooterDescription,
    getFooterLinks,
    getCopyrightText,
  }
})
