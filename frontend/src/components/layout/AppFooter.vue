<script setup lang="ts">
import { ref } from 'vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const footerLogoFailed = ref(false)

function onLogoError(el: Event) {
  const img = el.target as HTMLImageElement
  img.style.display = 'none'
  footerLogoFailed.value = true
}
</script>

<template>
  <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
    <!-- Main Footer Content -->
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">

        <!-- Column 1: Logo + Description -->
        <div class="sm:col-span-2 lg:col-span-1">
          <router-link to="/" class="flex items-center gap-2.5" :aria-label="appStore.getSiteName() + ' home'">
            <img
              v-if="appStore.getSiteLogo() && !footerLogoFailed"
              :src="appStore.getSiteLogo()"
              :alt="appStore.getSiteName()"
              class="h-8 w-auto"
              @error="onLogoError"
            />
            <svg v-if="!appStore.getSiteLogo() || footerLogoFailed" class="h-8 w-8 text-primary-600 dark:text-primary-400" viewBox="0 0 32 32" fill="none" aria-hidden="true">
              <path d="M8 22 L16 8 L24 22 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none" />
              <circle cx="16" cy="18" r="3" fill="currentColor" />
            </svg>
            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ appStore.getSiteName() }}</span>
          </router-link>
          <p
            v-if="appStore.getFooterDescription()"
            class="mt-4 max-w-xs text-sm leading-relaxed text-gray-500 dark:text-gray-400"
          >
            {{ appStore.getFooterDescription() }}
          </p>
        </div>

        <!-- Dynamic Link Sections -->
        <div
          v-for="section in appStore.getFooterLinks()"
          :key="section.title"
        >
          <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
            {{ section.title }}
          </h4>
          <ul class="mt-4 space-y-2.5">
            <li v-for="link in section.links" :key="link.url">
              <router-link
                v-if="!link.external"
                :to="link.url"
                class="text-sm text-gray-500 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              >
                {{ link.label }}
              </router-link>
              <a
                v-else
                :href="link.url"
                class="text-sm text-gray-500 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                target="_blank"
                rel="noopener noreferrer"
              >
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-gray-200 dark:border-gray-800">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <div class="text-xs text-gray-400 dark:text-gray-500">
          {{ appStore.getSiteName() }}
          <span v-if="appStore.getOrganization()">
            &middot; {{ appStore.getOrganization() }} Network
          </span>
        </div>
        <div class="text-xs text-gray-400 dark:text-gray-500">
          {{ appStore.getCopyrightText() }}
        </div>
      </div>
    </div>
  </footer>
</template>
