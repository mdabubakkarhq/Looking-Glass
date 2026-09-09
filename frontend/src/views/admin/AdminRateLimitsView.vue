<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminRateLimitEvent } from '@/types'

const events = ref<AdminRateLimitEvent[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const currentPage = ref(1)
const totalPages = ref(1)
const filterReason = ref('')

async function loadEvents(): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const params: Record<string, string> = { page: String(currentPage.value) }
    if (filterReason.value) params.reason = filterReason.value
    const qs = new URLSearchParams(params)
    const data = await adminApi.getRateLimits(qs.toString())
    events.value = data.data ?? []
    totalPages.value = data.last_page ?? 1
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load rate limit events'
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString()
}

onMounted(() => { void loadEvents() })
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rate Limit Events</h1>
    </div>

    <!-- Filters -->
    <div class="flex gap-3">
      <select
        v-model="filterReason"
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        @change="currentPage = 1; loadEvents()"
      >
        <option value="">All Reasons</option>
        <option value="per_ip">Per IP</option>
        <option value="per_target">Per Target</option>
        <option value="global">Global</option>
        <option value="multi_location">Multi-Location</option>
      </select>
    </div>

    <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
      {{ error }}
    </div>

    <div v-if="loading" class="py-8 text-center text-gray-500">Loading...</div>

    <div v-else-if="events.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800/30">
      No rate limit events found.
    </div>

    <div v-else class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-100 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-400">
          <tr>
            <th class="px-4 py-3">Time</th>
            <th class="px-4 py-3">Visitor Hash</th>
            <th class="px-4 py-3">Endpoint</th>
            <th class="px-4 py-3">Reason</th>
            <th class="px-4 py-3">Node</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
          <tr v-for="event in events" :key="event.id" class="bg-white dark:bg-gray-900/50">
            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ formatDate(event.created_at) }}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ event.visitor_hash }}</td>
            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ event.endpoint }}</td>
            <td class="px-4 py-3">
              <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">{{ event.reason }}</span>
            </td>
            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ event.node?.name ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2">
      <button
        :disabled="currentPage <= 1"
        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300"
        @click="currentPage--; loadEvents()"
      >
        Prev
      </button>
      <span class="text-sm text-gray-500 dark:text-gray-400">{{ currentPage }} / {{ totalPages }}</span>
      <button
        :disabled="currentPage >= totalPages"
        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300"
        @click="currentPage++; loadEvents()"
      >
        Next
      </button>
    </div>
  </div>
</template>
