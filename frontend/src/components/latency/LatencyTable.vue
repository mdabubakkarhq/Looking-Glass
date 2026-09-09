<script setup lang="ts">
import { useNodesStore } from '@/stores/nodes'

const nodesStore = useNodesStore()

function formatLatency(ms: number | null): string {
  if (ms === null || ms === undefined) return '—'
  return ms < 1 ? '<1 ms' : `${Math.round(ms)} ms`
}

function formatLoss(pct: number | null): string {
  if (pct === null || pct === undefined) return '—'
  return pct === 0 ? '0%' : `${pct.toFixed(1)}%`
}

function latencyColorClass(ms: number | null): string {
  if (ms === null) return 'text-gray-500'
  if (ms < 20) return 'text-green-600 dark:text-green-400'
  if (ms < 50) return 'text-emerald-600 dark:text-emerald-400'
  if (ms < 100) return 'text-yellow-600 dark:text-yellow-400'
  if (ms < 200) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}

function lossColorClass(pct: number | null): string {
  if (pct === null || pct === undefined) return 'text-gray-500'
  if (pct === 0) return 'text-green-600 dark:text-green-400'
  if (pct < 5) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}
</script>

<template>
  <section>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400">
        Live Node Latency
      </h2>
      <span v-if="nodesStore.latencyLoading" class="text-xs text-gray-500 dark:text-gray-400 animate-pulse">
        Updating...
      </span>
    </div>

    <div
      v-if="nodesStore.latencyLoading && nodesStore.latency.length === 0"
      class="text-sm text-gray-500"
    >
      Checking latency across all nodes...
    </div>

    <div
      v-else-if="nodesStore.latency.length === 0"
      class="text-sm text-gray-500"
    >
      No latency data available.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">Location</th>
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">Node</th>
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">IPv4</th>
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">IPv6</th>
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">Loss</th>
            <th class="whitespace-nowrap pb-2 font-medium">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="entry in nodesStore.latency"
            :key="entry.id"
            class="border-b border-gray-100 dark:border-gray-800/50"
          >
            <td class="whitespace-nowrap py-2 pr-6 text-gray-400">
              {{ entry.location }}
            </td>
            <td class="whitespace-nowrap py-2 pr-6 text-gray-900 dark:text-white">
              {{ entry.name }}
            </td>
            <td
              class="whitespace-nowrap py-2 pr-6 font-mono font-medium"
              :class="latencyColorClass(entry.ipv4_latency_ms)"
            >
              {{ formatLatency(entry.ipv4_latency_ms) }}
            </td>
            <td
              class="whitespace-nowrap py-2 pr-6 font-mono font-medium"
              :class="latencyColorClass(entry.ipv6_latency_ms)"
            >
              {{ formatLatency(entry.ipv6_latency_ms) }}
            </td>
            <td
              class="whitespace-nowrap py-2 pr-6 font-mono font-medium"
              :class="lossColorClass(entry.packet_loss_percent)"
            >
              {{ formatLoss(entry.packet_loss_percent) }}
            </td>
            <td class="py-2">
              <span
                class="inline-flex items-center gap-1.5 text-xs font-medium"
                :class="{
                  'text-green-600 dark:text-green-400': entry.status === 'online',
                  'text-red-600 dark:text-red-400': entry.status === 'offline',
                  'text-yellow-600 dark:text-yellow-400': entry.status === 'maintenance',
                }"
              >
                <span
                  class="inline-block h-2 w-2 rounded-full"
                  :class="{
                    'bg-green-500 dark:bg-green-400 animate-pulse-online': entry.status === 'online',
                    'bg-red-500 dark:bg-red-400': entry.status === 'offline',
                    'bg-yellow-500 dark:bg-yellow-400': entry.status === 'maintenance',
                  }"
                />
                {{ entry.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
