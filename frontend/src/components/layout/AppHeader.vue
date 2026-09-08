<script setup lang="ts">
import { ref } from 'vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const mobileMenuOpen = ref(false)

const navLinks = [
  { to: '/', label: 'Looking Glass' },
  { to: '/locations', label: 'Locations' },
  { to: '/network', label: 'Network' },
  { to: '/downloads', label: 'Downloads' },
  { to: '/status', label: 'Status' },
]
</script>

<template>
  <header class="sticky top-0 z-50 border-b border-gray-800 bg-gray-950/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
      <!-- Logo / Site Name -->
      <router-link
        to="/"
        class="flex items-center gap-2 text-lg font-bold text-white"
      >
        <svg class="h-8 w-8 text-primary-400" viewBox="0 0 32 32" fill="none">
          <path d="M8 22 L16 8 L24 22 Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none" />
          <circle cx="16" cy="18" r="3" fill="currentColor" />
        </svg>
        <span class="hidden sm:inline">{{ appStore.getSiteName() }}</span>
      </router-link>

      <!-- Desktop Nav -->
      <nav class="hidden items-center gap-1 md:flex">
        <router-link
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="rounded-lg px-3 py-2 text-sm font-medium text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
          active-class="!bg-gray-800 !text-white"
          :exact="link.to === '/'"
        >
          {{ link.label }}
        </router-link>
      </nav>

      <!-- Mobile Menu Button -->
      <button
        class="rounded-lg p-2 text-gray-400 hover:bg-gray-800 hover:text-white md:hidden"
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

    <!-- Mobile Nav -->
    <transition name="slide-up">
      <nav
        v-if="mobileMenuOpen"
        class="border-t border-gray-800 px-4 pb-4 md:hidden"
      >
        <router-link
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
          active-class="!bg-gray-800 !text-white"
          @click="mobileMenuOpen = false"
        >
          {{ link.label }}
        </router-link>
      </nav>
    </transition>
  </header>
</template>
