<script setup lang="ts">
import { useNodesStore } from '@/stores/nodes'

const nodesStore = useNodesStore()

function formatLatency(ms: number | null): string {
  if (ms === null || ms === undefined) return '—'
  return ms < 1 ? '<1 ms' : `${Math.round(ms)} ms`
}

function latencyColorClass(ms: number | null): string {
  if (ms === null) return 'text-gray-500'
  if (ms < 20) return 'text-green-400'
  if (ms < 50) return 'text-emerald-400'
  if (ms < 100) return 'text-yellow-400'
  if (ms < 200) return 'text-orange-400'
  return 'text-red-400'
}
</script>

<template>
  <section>
    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">
      Node Latency
    </h2>

    <div
      v-if="nodesStore.latencyLoading && nodesStore.latency.length === 0"
      class="text-sm text-gray-500"
    >
      Checking latency across all nodes...
    </div>

    <div
      v-else-if="nodesStore.latency.length === 0"
      class="text-sm text-gray-600"
    >
      No latency data available.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="border-b border-gray-800 text-gray-500">
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">Node</th>
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">Location</th>
            <th class="whitespace-nowrap pb-2 pr-6 font-medium">Latency</th>
            <th class="whitespace-nowrap pb-2 font-medium">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="entry in nodesStore.latency"
            :key="entry.node_id"
            class="border-b border-gray-800/50"
          >
            <td class="whitespace-nowrap py-2 pr-6 text-white">
              {{ entry.node_name }}
            </td>
            <td class="whitespace-nowrap py-2 pr-6 text-gray-400">
              {{ nodesStore.getNodeBySlug(entry.node_id)?.location ?? '—' }}
            </td>
            <td
              class="whitespace-nowrap py-2 pr-6 font-mono font-medium"
              :class="latencyColorClass(entry.latency_ms)"
            >
              {{ formatLatency(entry.latency_ms) }}
            </td>
            <td class="py-2">
              <span
                class="inline-flex items-center gap-1.5 text-xs font-medium"
                :class="{
                  'text-green-400': entry.status === 'online',
                  'text-red-400': entry.status === 'offline',
                  'text-yellow-400': entry.status === 'maintenance',
                }"
              >
                <span
                  class="inline-block h-2 w-2 rounded-full"
                  :class="{
                    'bg-green-400 animate-pulse-online': entry.status === 'online',
                    'bg-red-400': entry.status === 'offline',
                    'bg-yellow-400': entry.status === 'maintenance',
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
