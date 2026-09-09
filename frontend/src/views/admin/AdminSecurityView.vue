<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminSecurityEvent } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const events = ref<AdminSecurityEvent[]>([])
const loading = ref(true)
const filterSeverity = ref('')

onMounted(loadEvents)

async function loadEvents() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (filterSeverity.value) params.severity = filterSeverity.value
    const res = await adminApi.getSecurityEvents(params)
    events.value = res.data
  } catch { /* ignore */ } finally { loading.value = false }
}

function severityColor(s: string): string {
  const map: Record<string, string> = { critical: 'text-red-400', high: 'text-orange-400', medium: 'text-yellow-400', low: 'text-gray-400', info: 'text-blue-400' }
  return map[s] || 'text-gray-400'
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Security Events</h2>

    <div class="mb-4">
      <select v-model="filterSeverity" @change="loadEvents" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <option value="">All Severities</option>
        <option value="critical">Critical</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
        <option value="info">Info</option>
      </select>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading events..." /></div>
    <div v-else class="space-y-2">
      <div v-for="e in events" :key="e.id" class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium" :class="severityColor(e.severity)">{{ e.severity }}</span>
          <span class="text-sm text-gray-900 dark:text-white">{{ e.event_type }}</span>
          <span v-if="e.node" class="text-xs text-gray-500">{{ e.node.name }}</span>
          <span class="ml-auto text-xs text-gray-600">{{ new Date(e.created_at).toLocaleString() }}</span>
        </div>
        <div class="mt-1 text-xs text-gray-400">{{ e.description }}</div>
      </div>
      <div v-if="events.length === 0" class="py-12 text-center text-gray-500">No security events.</div>
    </div>
  </div>
</template>
