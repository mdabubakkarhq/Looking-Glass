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

function statusColor(s: string): string {
  const map: Record<string, string> = { completed: 'text-green-400', running: 'text-primary-400', pending: 'text-yellow-400', failed: 'text-red-400' }
  return map[s] || 'text-gray-400'
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-white">Test History</h2>

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filterType" @change="loadTests" class="rounded border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white">
        <option value="">All Types</option>
        <option value="ping">Ping</option>
        <option value="traceroute">Traceroute</option>
        <option value="mtr">MTR</option>
        <option value="dns">DNS</option>
      </select>
      <select v-model="filterStatus" @change="loadTests" class="rounded border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="running">Running</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
      </select>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading tests..." /></div>
    <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/20 px-4 py-3 text-red-400">{{ error }}</div>

    <div v-else class="space-y-2">
      <div v-for="t in tests" :key="t.uuid" class="flex items-center justify-between rounded-lg border border-gray-800 bg-gray-900 px-4 py-3">
        <div>
          <div class="flex items-center gap-2">
            <span class="rounded bg-gray-800 px-2 py-0.5 text-xs font-mono text-gray-300">{{ t.test_type }}</span>
            <span class="font-mono text-sm text-white">{{ t.target }}</span>
            <span class="text-sm font-medium" :class="statusColor(t.status)">{{ t.status }}</span>
          </div>
          <div class="mt-1 text-xs text-gray-500">
            {{ t.node?.name || t.node_id }}
            {{ t.runtime_ms ? '  ' + t.runtime_ms + 'ms' : '' }}
            {{ '  ' + new Date(t.created_at).toLocaleString() }}
          </div>
        </div>
        <button class="rounded border border-red-800 px-2 py-1 text-xs text-red-400 hover:bg-red-900/30" @click="deleteTest(t.uuid)">Delete</button>
      </div>
      <div v-if="tests.length === 0" class="py-12 text-center text-gray-500">No tests found.</div>
    </div>
  </div>
</template>
