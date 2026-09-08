<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminDashboardData } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const data = ref<AdminDashboardData | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const res = await adminApi.getDashboard()
    data.value = res.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
})

function statusColor(status: string): string {
  return status === 'ok' ? 'text-green-400' : 'text-red-400'
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-white">Dashboard</h2>

    <div v-if="loading" class="py-16">
      <LoadingSpinner size="lg" label="Loading dashboard..." />
    </div>

    <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/20 px-4 py-3 text-red-400">
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- Stats Cards -->
      <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-4">
          <div class="text-sm text-gray-500">Online Nodes</div>
          <div class="mt-1 text-3xl font-bold text-green-400">{{ data.nodes.active }}</div>
        </div>
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-4">
          <div class="text-sm text-gray-500">Tests Today</div>
          <div class="mt-1 text-3xl font-bold text-white">{{ data.tests.today }}</div>
        </div>
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-4">
          <div class="text-sm text-gray-500">Running</div>
          <div class="mt-1 text-3xl font-bold text-primary-400">{{ data.tests.running }}</div>
        </div>
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-4">
          <div class="text-sm text-gray-500">Rate Limited</div>
          <div class="mt-1 text-3xl font-bold text-yellow-400">{{ data.rate_limits.today }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- System Health -->
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-5">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">System Health</h3>
          <div class="space-y-3">
            <div v-for="(status, service) in data.system" :key="service" class="flex items-center justify-between">
              <span class="text-gray-300 capitalize">{{ service }}</span>
              <span class="font-mono font-bold" :class="statusColor(status)">{{ status === 'ok' ? 'OK' : status }}</span>
            </div>
          </div>
        </div>

        <!-- Node Summary -->
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-5">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">Nodes</h3>
          <div class="grid grid-cols-2 gap-3">
            <div class="text-center">
              <div class="text-2xl font-bold text-white">{{ data.nodes.total }}</div>
              <div class="text-xs text-gray-500">Total</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-400">{{ data.nodes.active }}</div>
              <div class="text-xs text-gray-500">Online</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-red-400">{{ data.nodes.offline }}</div>
              <div class="text-xs text-gray-500">Offline</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-400">{{ data.nodes.maintenance }}</div>
              <div class="text-xs text-gray-500">Maintenance</div>
            </div>
          </div>
        </div>

        <!-- Top Targets -->
        <div class="rounded-lg border border-gray-800 bg-gray-900 p-5 lg:col-span-2">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">Top Targets Today</h3>
          <div v-if="data.top_targets.length === 0" class="text-sm text-gray-600">No tests today</div>
          <div v-else class="space-y-2">
            <div
              v-for="t in data.top_targets"
              :key="t.target"
              class="flex items-center justify-between rounded bg-gray-800 px-3 py-2"
            >
              <span class="font-mono text-sm text-white">{{ t.target }}</span>
              <span class="text-sm text-gray-400">{{ t.test_count }} tests</span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
