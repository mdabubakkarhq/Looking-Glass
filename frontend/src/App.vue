<script setup lang="ts">
import { onMounted } from 'vue'
import { useAppStore } from '@/stores/app'
import { useNodesStore } from '@/stores/nodes'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'

const appStore = useAppStore()
const nodesStore = useNodesStore()

/**
 * Set or update a <meta> tag in the document head.
 * Uses `attrName` to distinguish between `name` and `property` attributes.
 */
function setMeta(content: string, attrValue: string, attrName: 'name' | 'property' = 'name'): void {
  if (!content) return
  let el = document.querySelector(`meta[${attrName}="${attrValue}"]`) as HTMLMetaElement | null
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attrName, attrValue)
    document.head.appendChild(el)
  }
  el.setAttribute('content', content)
}

onMounted(async () => {
  await Promise.all([
    appStore.loadConfig(),
    nodesStore.loadNodes(),
  ])

  // ── Dynamic Favicon ────────────────────────────────────────
  const faviconUrl = appStore.getFavicon()
  if (faviconUrl) {
    // Remove all existing icon links (covers rel="icon" and rel="shortcut icon")
    document.querySelectorAll("link[rel~='icon']").forEach(el => el.remove())

    const link = document.createElement('link')
    link.rel = 'icon'
    // Guess type from extension; default to generic image
    if (faviconUrl.endsWith('.svg')) link.type = 'image/svg+xml'
    else if (faviconUrl.endsWith('.png')) link.type = 'image/png'
    else if (faviconUrl.endsWith('.ico')) link.type = 'image/x-icon'
    else link.type = 'image/png'
    link.href = faviconUrl
    document.head.appendChild(link)
  }

  // ── SEO & Open Graph Meta Tags ─────────────────────────────
  const siteTitle = appStore.getSiteTitle() || appStore.getSiteName()
  const metaDesc = appStore.getMetaDescription()
  const ogImage = appStore.getOgImage()
  const origin = window.location.origin

  // Page title (only set the default; router beforeEach handles per-page titles)
  if (siteTitle && document.title === 'Open Looking Glass') {
    document.title = siteTitle
  }

  // Standard meta
  setMeta(metaDesc, 'description')

  // Open Graph
  setMeta(siteTitle, 'og:title', 'property')
  setMeta(metaDesc, 'og:description', 'property')
  setMeta(origin, 'og:url', 'property')
  setMeta('website', 'og:type', 'property')
  if (ogImage) {
    setMeta(ogImage, 'og:image', 'property')
  }

  // Twitter Card
  setMeta(ogImage ? 'summary_large_image' : 'summary', 'twitter:card')
  setMeta(siteTitle, 'twitter:title')
  setMeta(metaDesc, 'twitter:description')
  if (ogImage) {
    setMeta(ogImage, 'twitter:image')
  }
})
</script>

<template>
  <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
    <AppHeader />
    <main class="flex-1">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    <AppFooter />
  </div>
</template>
