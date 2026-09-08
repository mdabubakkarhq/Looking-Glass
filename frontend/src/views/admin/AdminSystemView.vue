<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { SystemInfo } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const info = ref<SystemInfo | null>(null)
const health = ref<{ healthy: boolean; checks: Record<string, string> } | null>(null)
const loading = ref(true)
const migrating = ref(false)

onMounted(async () => {
  try {
    const [infoRes, healthRes] = await Promise.all([
      adminApi.getSystemInfo(),
      adminApi.getSystemHealth(),
    ])
    info.value = infoRes.data
    health.value = healthRes
  } catch { /* ignore */ } finally { loading.value = false }
})

async function runMigrations() {
  if (!confirm('Run database migrations?')) return
  migrating.value = true
  try {
    const res = await adminApi.runMigrations()
    alert(res.message)
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Migration failed')
  } finally {
    migrating.value = false
  }
}

function statusIcon(s: string): string {
  return s === 'ok' ? 'OK' : s.toUpperCase()
}
function statusColor(s: string): string {
  return s === 'ok' ? 'text-green-400' : 'text-red-400'
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-white">System</h2>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading system info..." /></div>

    <template v-else>
      <!-- Health -->
      <div class="mb-6 rounded-lg border border-gray-800 bg-gray-900 p-5">
        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">Health Checks</h3>
        <div v-if="health" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="(status, check) in health.checks" :key="check" class="rounded bg-gray-800 px-4 py-3 text-center">
            <div class="font-mono text-lg font-bold" :class="statusColor(status)">{{ statusIcon(status) }}</div>
            <div class="mt-1 text-xs text-gray-500 capitalize">{{ check }}</div>
          </div>
        </div>
      </div>

      <!-- System Info -->
      <div v-if="info" class="mb-6 rounded-lg border border-gray-800 bg-gray-900 p-5">
        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">System Information</h3>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div><dt class="text-xs text-gray-500">Version</dt><dd class="font-mono text-sm text-white">{{ info.version }}</dd></div>
          <div><dt class="text-xs text-gray-500">Environment</dt><dd class="font-mono text-sm text-white">{{ info.environment }}</dd></div>
          <div><dt class="text-xs text-gray-500">PHP Version</dt><dd class="font-mono text-sm text-white">{{ info.php_version }}</dd></div>
          <div><dt class="text-xs text-gray-500">Laravel Version</dt><dd class="font-mono text-sm text-white">{{ info.laravel_version }}</dd></div>
          <div><dt class="text-xs text-gray-500">Database</dt><dd class="font-mono text-sm text-white">{{ info.database_driver }}</dd></div>
          <div><dt class="text-xs text-gray-500">Queue</dt><dd class="font-mono text-sm text-white">{{ info.queue_connection }}</dd></div>
          <div><dt class="text-xs text-gray-500">Cache</dt><dd class="font-mono text-sm text-white">{{ info.cache_driver }}</dd></div>
          <div><dt class="text-xs text-gray-500">Total Nodes</dt><dd class="font-mono text-sm text-white">{{ info.nodes_total }}</dd></div>
          <div><dt class="text-xs text-gray-500">Total Tests</dt><dd class="font-mono text-sm text-white">{{ info.tests_total }}</dd></div>
        </dl>
      </div>

      <!-- Actions -->
      <div class="rounded-lg border border-gray-800 bg-gray-900 p-5">
        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-400">Actions</h3>
        <div class="flex gap-3">
          <button
            :disabled="migrating"
            class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300 hover:border-gray-600 hover:text-white disabled:opacity-50"
            @click="runMigrations"
          >
            {{ migrating ? 'Running...' : 'Run Migrations' }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
