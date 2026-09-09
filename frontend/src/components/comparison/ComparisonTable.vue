<script setup lang="ts">
import { useComparisonStore } from '@/stores/comparison'
import type { ComparisonEntry } from '@/stores/comparison'

const comp = useComparisonStore()

function formatMs(ms: number | null): string {
  if (ms === null || ms === undefined) return '—'
  return `${ms.toFixed(1)} ms`
}

function formatLoss(pct: number | null): string {
  if (pct === null || pct === undefined) return '—'
  return `${pct.toFixed(1)}%`
}

function getStatusLabel(entry: ComparisonEntry): string {
  if (entry.status === 'completed' && entry.result) {
    const loss = entry.result.packet_loss
    const latency = entry.result.latency_avg
    if (loss !== null && loss > 50) return 'Poor'
    if (loss !== null && loss > 0) return 'Fair'
    if (latency !== null && latency > 200) return 'Moderate'
    if (latency !== null && latency > 100) return 'Good'
    return 'Excellent'
  }
  if (entry.status === 'failed') return 'Failed'
  if (entry.status === 'running') return 'Running...'
  if (entry.status === 'pending') return 'Pending'
  return '—'
}

function getStatusColor(entry: ComparisonEntry): string {
  if (entry.status === 'completed' && entry.result) {
    const loss = entry.result.packet_loss
    if (loss !== null && loss > 50) return 'text-red-600 dark:text-red-400'
    if (loss !== null && loss > 0) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-green-600 dark:text-green-400'
  }
  if (entry.status === 'failed') return 'text-red-600 dark:text-red-400'
  return 'text-gray-400'
}
</script>

<template>
  <div v-if="comp.hasResults" class="space-y-4">
    <!-- Comparison Table -->
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
          <tr>
            <th class="px-4 py-3">Location</th>
            <th class="px-4 py-3">Status</th>
            <th v-if="comp.testType === 'ping' || comp.testType === 'mtr'" class="px-4 py-3">Latency</th>
            <th v-if="comp.testType === 'ping' || comp.testType === 'mtr'" class="px-4 py-3">Loss</th>
            <th v-if="comp.testType === 'traceroute' || comp.testType === 'mtr'" class="px-4 py-3">Hops</th>
            <th v-if="comp.testType === 'dns'" class="px-4 py-3">Resolved IP</th>
            <th class="px-4 py-3">Runtime</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
          <tr
            v-for="[nodeId, entry] in comp.entries"
            :key="nodeId"
            class="bg-white dark:bg-gray-900/50"
          >
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ entry.nodeName }}</td>
            <td class="px-4 py-3">
              <span :class="getStatusColor(entry)" class="font-medium">
                {{ getStatusLabel(entry) }}
              </span>
              <div v-if="entry.error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ entry.error }}</div>
            </td>
            <td
              v-if="comp.testType === 'ping' || comp.testType === 'mtr'"
              class="px-4 py-3 font-mono text-primary-600 dark:text-primary-400"
            >
              {{ entry.result ? formatMs(entry.result.latency_avg) : '—' }}
            </td>
            <td
              v-if="comp.testType === 'ping' || comp.testType === 'mtr'"
              class="px-4 py-3 font-mono"
              :class="entry.result?.packet_loss && entry.result.packet_loss > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
            >
              {{ entry.result ? formatLoss(entry.result.packet_loss) : '—' }}
            </td>
            <td
              v-if="comp.testType === 'traceroute' || comp.testType === 'mtr'"
              class="px-4 py-3 font-mono text-gray-900 dark:text-white"
            >
              {{ entry.result?.hop_count ?? '—' }}
            </td>
            <td v-if="comp.testType === 'dns'" class="px-4 py-3 font-mono text-gray-900 dark:text-white">
              {{ entry.result?.resolved_ip ?? '—' }}
            </td>
            <td class="px-4 py-3 font-mono text-gray-400">
              {{ entry.result?.runtime_ms ? `${entry.result.runtime_ms} ms` : '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Per-node output details (collapsible) -->
    <div v-for="[nodeId, entry] in comp.entries" :key="`output-${nodeId}`">
      <details
        v-if="entry.outputLines.length > 0"
        class="rounded-lg border border-gray-200 dark:border-gray-700"
      >
        <summary class="cursor-pointer bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-white">
          {{ entry.nodeName }} — Raw Output ({{ entry.outputLines.length }} lines)
        </summary>
        <div class="max-h-60 overflow-y-auto bg-[#0f172a] p-4 font-mono text-xs text-gray-300">
          <div v-for="(line, idx) in entry.outputLines" :key="idx">{{ line }}</div>
        </div>
      </details>
    </div>

    <!-- Reset Button -->
    <div v-if="comp.allCompleted">
      <button
        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white"
        @click="comp.reset()"
      >
        New Comparison
      </button>
    </div>
  </div>
</template>
