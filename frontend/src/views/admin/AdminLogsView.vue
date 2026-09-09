<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminSecurityEvent, AdminRateLimitEvent } from '@/types'

const activeTab = ref<'rate-limits' | 'security'>('rate-limits')
const rateLimitEvents = ref<AdminRateLimitEvent[]>([])
const securityEvents = ref<AdminSecurityEvent[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const currentPage = ref(1)
const totalPages = ref(1)

async function loadRateLimits(): Promise<void> {
  loading.value = true; error.value = null
  try {
    const data = await adminApi.getRateLimits(`page=${currentPage.value}&per_page=50`)
    rateLimitEvents.value = data.data ?? []
    totalPages.value = data.last_page ?? 1
  } catch (err) { error.value = err instanceof Error ? err.message : 'Failed to load' }
  finally { loading.value = false }
}

async function loadSecurityLogs(): Promise<void> {
  loading.value = true; error.value = null
  try {
    const data = await adminApi.getSecurityEvents({ page: currentPage.value })
    securityEvents.value = data.data ?? []
    totalPages.value = data.last_page ?? 1
  } catch (err) { error.value = err instanceof Error ? err.message : 'Failed to load' }
  finally { loading.value = false }
}

function loadActive(): void {
  if (activeTab.value === 'rate-limits') void loadRateLimits()
  else void loadSecurityLogs()
}

function switchTab(tab: 'rate-limits' | 'security'): void {
  activeTab.value = tab; currentPage.value = 1; loadActive()
}

function formatDate(d: string): string { return new Date(d).toLocaleString() }
function severityColor(s: string): string {
  if (s === 'critical') return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  if (s === 'warning') return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
  return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
}

onMounted(() => { void loadRateLimits() })
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Logs</h1>

    <div class="flex gap-1 rounded-lg border border-gray-200 p-1 bg-gray-100 w-fit dark:border-gray-700 dark:bg-gray-800">
      <button
        class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
        :class="activeTab === 'rate-limits' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
        @click="switchTab('rate-limits')"
      >Rate Limits</button>
      <button
        class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
        :class="activeTab === 'security' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
        @click="switchTab('security')"
      >Security</button>
    </div>

    <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">{{ error }}</div>
    <div v-if="loading" class="py-8 text-center text-gray-500">Loading...</div>

    <!-- Rate Limits -->
    <template v-else-if="activeTab === 'rate-limits'">
      <div v-if="rateLimitEvents.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800/30">No rate limit events.</div>
      <div v-else class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-100 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-400">
            <tr><th class="px-4 py-3">Time</th><th class="px-4 py-3">Visitor</th><th class="px-4 py-3">Endpoint</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3">Node</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
            <tr v-for="e in rateLimitEvents" :key="e.id" class="bg-white dark:bg-gray-900/50">
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ formatDate(e.created_at) }}</td>
              <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ e.visitor_hash }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ e.endpoint }}</td>
              <td class="px-4 py-3"><span class="rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">{{ e.reason }}</span></td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ e.node?.name ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Security -->
    <template v-else>
      <div v-if="securityEvents.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800/30">No security events.</div>
      <div v-else class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-100 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-400">
            <tr><th class="px-4 py-3">Time</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Severity</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Node</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
            <tr v-for="e in securityEvents" :key="e.id" class="bg-white dark:bg-gray-900/50">
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ formatDate(e.created_at) }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ e.event_type }}</td>
              <td class="px-4 py-3"><span :class="severityColor(e.severity)" class="rounded px-2 py-0.5 text-xs">{{ e.severity }}</span></td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ e.description }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ e.node?.name ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2">
      <button :disabled="currentPage <= 1" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300" @click="currentPage--; loadActive()">Prev</button>
      <span class="text-sm text-gray-500 dark:text-gray-400">{{ currentPage }} / {{ totalPages }}</span>
      <button :disabled="currentPage >= totalPages" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300" @click="currentPage++; loadActive()">Next</button>
    </div>
  </div>
</template>
