<script setup lang="ts">
import { useTestStore } from '@/stores/test'
import StatusBadge from '@/components/common/StatusBadge.vue'

const testStore = useTestStore()

function formatMs(ms: number | null): string {
  if (ms === null || ms === undefined) return '—'
  return `${ms} ms`
}

function formatLoss(pct: number | null): string {
  if (pct === null || pct === undefined) return '—'
  return `${pct.toFixed(1)}%`
}
</script>

<template>
  <div
    v-if="testStore.testResult && testStore.hasResult"
    class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/50"
  >
    <div class="mb-3 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Results</h3>
      <StatusBadge :status="testStore.testResult.status" />
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <!-- Runtime -->
      <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
        <div class="text-xs text-gray-500">Runtime</div>
        <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
          {{ testStore.testResult.runtime_ms !== null ? `${testStore.testResult.runtime_ms} ms` : '—' }}
        </div>
      </div>

      <!-- Packet Loss (Ping/MTR) -->
      <div
        v-if="testStore.testResult.packet_loss !== null"
        class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
      >
        <div class="text-xs text-gray-500">Packet Loss</div>
        <div
          class="mt-1 text-lg font-semibold"
          :class="testStore.testResult.packet_loss > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
        >
          {{ formatLoss(testStore.testResult.packet_loss) }}
        </div>
      </div>

      <!-- Latency Avg (Ping) -->
      <div
        v-if="testStore.testResult.latency_avg !== null"
        class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
      >
        <div class="text-xs text-gray-500">Avg Latency</div>
        <div class="mt-1 text-lg font-semibold text-primary-600 dark:text-primary-400">
          {{ formatMs(testStore.testResult.latency_avg) }}
        </div>
      </div>

      <!-- Hop Count (Traceroute/MTR) -->
      <div
        v-if="testStore.testResult.hop_count !== null"
        class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
      >
        <div class="text-xs text-gray-500">Hops</div>
        <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
          {{ testStore.testResult.hop_count }}
        </div>
      </div>

      <!-- Resolved IP (DNS) -->
      <div
        v-if="testStore.testResult.resolved_ip"
        class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
      >
        <div class="text-xs text-gray-500">Resolved IP</div>
        <div class="mt-1 text-sm font-mono font-semibold text-gray-900 dark:text-white">
          {{ testStore.testResult.resolved_ip }}
        </div>
      </div>
    </div>

    <!-- Latency Breakdown (Ping) -->
    <div
      v-if="testStore.testResult.latency_min !== null"
      class="mt-3 grid grid-cols-3 gap-2"
    >
      <div class="rounded bg-gray-100 px-3 py-2 text-center dark:bg-gray-800/50">
        <div class="text-xs text-gray-500">Min</div>
        <div class="text-sm font-medium text-green-600 dark:text-green-400">
          {{ formatMs(testStore.testResult.latency_min) }}
        </div>
      </div>
      <div class="rounded bg-gray-100 px-3 py-2 text-center dark:bg-gray-800/50">
        <div class="text-xs text-gray-500">Avg</div>
        <div class="text-sm font-medium text-primary-600 dark:text-primary-400">
          {{ formatMs(testStore.testResult.latency_avg) }}
        </div>
      </div>
      <div class="rounded bg-gray-100 px-3 py-2 text-center dark:bg-gray-800/50">
        <div class="text-xs text-gray-500">Max</div>
        <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
          {{ formatMs(testStore.testResult.latency_max) }}
        </div>
      </div>
    </div>

    <!-- Error Details -->
    <div
      v-if="testStore.testResult.error_message"
      class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
    >
      <span class="font-semibold">Error:</span> {{ testStore.testResult.error_message }}
    </div>
  </div>
</template>
