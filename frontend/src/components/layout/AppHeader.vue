<script setup lang="ts">
import { ref } from 'vue'
import { useAppStore } from '@/stores/app'
import { useTheme } from '@/composables/useTheme'

const appStore = useAppStore()
const { theme, toggleTheme } = useTheme()
const mobileMenuOpen = ref(false)
const logoFailed = ref(false)

function onLogoError(el: Event) {
  const img = el.target as HTMLImageElement
  img.style.display = 'none'
  logoFailed.value = true
}
</script>

<template>
  <header class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
      <!-- Logo / Site Name -->
      <router-link
        to="/"
        class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white"
        :aria-label="appStore.getSiteName() + ' home'"
      >
        <img
          v-if="appStore.getSiteLogo() && !logoFailed"
          :src="appStore.getSiteLogo()"
          :alt="appStore.getSiteName()"
          class="h-8 w-auto"
          @error="onLogoError"
        />
        <svg v-if="!appStore.getSiteLogo() || logoFailed" class="h-8 w-8 text-primary-600 dark:text-primary-400" viewBox="0 0 32 32" fill="none" aria-hidden="true">
          <path d="M8 22 L16 8 L24 22 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none" />
          <circle cx="16" cy="18" r="3" fill="currentColor" />
        </svg>
        <span v-if="!appStore.getSiteLogo() || logoFailed" class="hidden sm:inline">{{ appStore.getSiteName() }}</span>
      </router-link>

      <!-- Desktop Nav -->
      <nav class="hidden items-center gap-1 md:flex">
        <template v-for="link in appStore.getMenuItems()" :key="link.url">
          <router-link
            v-if="!link.open_new_tab"
            :to="link.url"
            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            active-class="!bg-gray-100 !text-gray-900 dark:!bg-gray-800 dark:!text-white"
            :exact="link.url === '/'"
          >
            {{ link.label }}
          </router-link>
          <a
            v-else
            :href="link.url"
            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ link.label }}
          </a>
        </template>
      </nav>

      <!-- Right Side: Theme Toggle + Mobile Menu -->
      <div class="flex items-center gap-1">
        <!-- Theme Toggle -->
        <button
          class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
          @click="toggleTheme"
          :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <!-- Sun icon (shown in dark mode to switch to light) -->
          <svg v-if="theme === 'dark'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <!-- Moon icon (shown in light mode to switch to dark) -->
          <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>
        </button>

        <!-- Mobile Menu Button -->
        <button
          class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white md:hidden"
          @click="mobileMenuOpen = !mobileMenuOpen"
          aria-label="Toggle menu"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              v-if="!mobileMenuOpen"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Nav -->
    <transition name="slide-up">
      <nav
        v-if="mobileMenuOpen"
        class="border-t border-gray-200 px-4 pb-4 dark:border-gray-800 md:hidden"
      >
        <template v-for="link in appStore.getMenuItems()" :key="link.url">
          <router-link
            v-if="!link.open_new_tab"
            :to="link.url"
            class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            active-class="!bg-gray-100 !text-gray-900 dark:!bg-gray-800 dark:!text-white"
            @click="mobileMenuOpen = false"
          >
            {{ link.label }}
          </router-link>
          <a
            v-else
            :href="link.url"
            class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            target="_blank"
            rel="noopener noreferrer"
            @click="mobileMenuOpen = false"
          >
            {{ link.label }}
          </a>
        </template>
      </nav>
    </transition>
  </header>
</template>
