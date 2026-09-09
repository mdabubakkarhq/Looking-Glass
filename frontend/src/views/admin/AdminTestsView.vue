<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminNetworkTest } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const tests = ref<AdminNetworkTest[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const filterType = ref('')
const filterStatus = ref('')

// Purge state
const showPurge = ref(false)
const purgeDays = ref(7)
const purgeStatus = ref('all')
const purging = ref(false)
const purgeResult = ref<string | null>(null)

onMounted(loadTests)

async function loadTests() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (filterType.value) params.test_type = filterType.value
    if (filterStatus.value) params.status = filterStatus.value
    const res = await adminApi.getTests(params)
    tests.value = res.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load tests'
  } finally {
    loading.value = false
  }
}

async function deleteTest(uuid: string) {
  if (!confirm('Delete this test?')) return
  try {
    await adminApi.deleteTest(uuid)
    tests.value = tests.value.filter(t => t.uuid !== uuid)
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed')
  }
}

async function runPurge() {
  if (!confirm(`Purge completed/failed tests older than ${purgeDays.value} days? This cannot be undone.`)) return
  purging.value = true
  purgeResult.value = null
  try {
    const res = await adminApi.purgeTests({ days: purgeDays.value, status: purgeStatus.value })
    purgeResult.value = res.message
    // Reload the list
    await loadTests()
  } catch (err) {
    purgeResult.value = err instanceof Error ? err.message : 'Purge failed'
  } finally {
    purging.value = false
  }
}

function statusColor(s: string): string {
  const map: Record<string, string> = { completed: 'text-green-400', running: 'text-primary-400', pending: 'text-yellow-400', failed: 'text-red-400' }
  return map[s] || 'text-gray-400'
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Test History</h2>

    <!-- Purge Panel -->
    <div class="mb-4">
      <button class="rounded-lg border border-orange-300 px-3 py-1.5 text-sm font-medium text-orange-600 hover:bg-orange-50 dark:border-orange-800 dark:text-orange-400 dark:hover:bg-orange-900/20" @click="showPurge = !showPurge">
        {{ showPurge ? 'Hide Purge Options' : '🧹 Purge Old Tests' }}
      </button>
      <div v-if="showPurge" class="mt-3 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/10">
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Older than (days)</label>
            <input v-model.number="purgeDays" type="number" min="1" max="365" class="w-24 rounded border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
            <select v-model="purgeStatus" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              <option value="all">Completed + Failed</option>
              <option value="completed">Completed Only</option>
              <option value="failed">Failed Only</option>
            </select>
          </div>
          <button :disabled="purging" class="rounded-lg bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-500 disabled:opacity-50" @click="runPurge">
            {{ purging ? 'Purging...' : 'Run Purge' }}
          </button>
        </div>
        <div v-if="purgeResult" class="mt-2 text-sm" :class="purgeResult.startsWith('Purged') ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
          {{ purgeResult }}
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filterType" @change="loadTests" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <option value="">All Types</option>
        <option value="ping">Ping</option>
        <option value="traceroute">Traceroute</option>
        <option value="mtr">MTR</option>
        <option value="dns">DNS</option>
      </select>
      <select v-model="filterStatus" @change="loadTests" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="running">Running</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
      </select>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading tests..." /></div>
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">{{ error }}</div>

    <div v-else class="space-y-2">
      <div v-for="t in tests" :key="t.uuid" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
        <div>
          <div class="flex items-center gap-2">
            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ t.test_type }}</span>
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ t.target }}</span>
            <span class="text-sm font-medium" :class="statusColor(t.status)">{{ t.status }}</span>
          </div>
          <div class="mt-1 text-xs text-gray-500">
            {{ t.node?.name || t.node_id }}
            {{ t.runtime_ms ? '  ' + t.runtime_ms + 'ms' : '' }}
            {{ '  ' + new Date(t.created_at).toLocaleString() }}
          </div>
        </div>
        <button class="rounded border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30" @click="deleteTest(t.uuid)">Delete</button>
      </div>
      <div v-if="tests.length === 0" class="py-12 text-center text-gray-500">No tests found.</div>
    </div>
  </div>
</template>
