<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '@/api/client'
import type { SystemStatus } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const status = ref<SystemStatus | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const response = await api.getStatus()
    status.value = response.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load system status'
  } finally {
    loading.value = false
  }
})

function serviceStatusClass(s: string): string {
  return s === 'ok' ? 'text-green-400' : 'text-red-400'
}

function serviceStatusIcon(s: string): string {
  return s === 'ok' ? '✓' : '✗'
}
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-white sm:text-3xl">System Status</h1>
      <p class="mt-2 text-gray-400">
        Current health and status of the Looking Glass platform.
      </p>
    </div>

    <div v-if="loading" class="py-16">
      <LoadingSpinner size="lg" label="Checking system status..." />
    </div>

    <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/20 px-4 py-3 text-red-400">
      {{ error }}
    </div>

    <div v-else-if="status" class="space-y-6">
      <!-- Services Health -->
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-6">
        <h2 class="mb-4 text-lg font-semibold text-white">Services</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="flex items-center justify-between rounded-lg bg-gray-800 px-4 py-3">
            <span class="text-gray-300">Controller</span>
            <span
              class="font-mono text-lg font-bold"
              :class="serviceStatusClass(status.controller)"
            >
              {{ serviceStatusIcon(status.controller) }}
            </span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-gray-800 px-4 py-3">
            <span class="text-gray-300">Database</span>
            <span
              class="font-mono text-lg font-bold"
              :class="serviceStatusClass(status.database)"
            >
              {{ serviceStatusIcon(status.database) }}
            </span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-gray-800 px-4 py-3">
            <span class="text-gray-300">Redis</span>
            <span
              class="font-mono text-lg font-bold"
              :class="serviceStatusClass(status.redis)"
            >
              {{ serviceStatusIcon(status.redis) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Node Status -->
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-6">
        <h2 class="mb-4 text-lg font-semibold text-white">Nodes</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-lg bg-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-white">{{ status.nodes.total }}</div>
            <div class="mt-1 text-sm text-gray-500">Total</div>
          </div>
          <div class="rounded-lg bg-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-green-400">{{ status.nodes.online }}</div>
            <div class="mt-1 text-sm text-gray-500">Online</div>
          </div>
          <div class="rounded-lg bg-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-red-400">{{ status.nodes.offline }}</div>
            <div class="mt-1 text-sm text-gray-500">Offline</div>
          </div>
          <div class="rounded-lg bg-gray-800 p-4 text-center">
            <div class="text-3xl font-bold text-yellow-400">{{ status.nodes.maintenance }}</div>
            <div class="mt-1 text-sm text-gray-500">Maintenance</div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-6">
        <h2 class="mb-4 text-lg font-semibold text-white">Statistics</h2>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <dt class="text-sm text-gray-500">Tests Today</dt>
            <dd class="mt-1 text-2xl font-bold text-white">{{ status.tests_today }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Version</dt>
            <dd class="mt-1 font-mono text-2xl font-bold text-primary-400">{{ status.version }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Last Updated</dt>
            <dd class="mt-1 text-sm text-gray-300">
              {{ new Date(status.generated_at).toLocaleString() }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>
