<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminDashboardData, SystemInfo } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const data = ref<AdminDashboardData | null>(null)
const sysInfo = ref<SystemInfo | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const migrating = ref(false)

onMounted(async () => {
  try {
    const [dashRes, infoRes] = await Promise.all([
      adminApi.getDashboard(),
      adminApi.getSystemInfo(),
    ])
    data.value = dashRes.data
    sysInfo.value = infoRes.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
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

function statusColor(status: string): string {
  return status === 'ok' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Dashboard</h2>

    <div v-if="loading" class="py-16">
      <LoadingSpinner size="lg" label="Loading dashboard..." />
    </div>

    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- Stats Cards -->
      <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <div class="text-sm text-gray-500">Online Nodes</div>
          <div class="mt-1 text-3xl font-bold text-green-600 dark:text-green-400">{{ data.nodes.active }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <div class="text-sm text-gray-500">Tests Today</div>
          <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ data.tests.today }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <div class="text-sm text-gray-500">Running</div>
          <div class="mt-1 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ data.tests.running }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
          <div class="text-sm text-gray-500">Rate Limited</div>
          <div class="mt-1 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ data.rate_limits.today }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- System Health -->
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">System Health</h3>
          <div class="space-y-3">
            <div v-for="(status, service) in data.system" :key="service" class="flex items-center justify-between">
              <span class="text-gray-700 dark:text-gray-300 capitalize">{{ service }}</span>
              <span class="font-mono font-bold" :class="statusColor(status)">{{ status === 'ok' ? 'OK' : status }}</span>
            </div>
          </div>
        </div>

        <!-- Node Summary -->
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nodes</h3>
          <div class="grid grid-cols-2 gap-3">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ data.nodes.total }}</div>
              <div class="text-xs text-gray-500">Total</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ data.nodes.active }}</div>
              <div class="text-xs text-gray-500">Online</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ data.nodes.offline }}</div>
              <div class="text-xs text-gray-500">Offline</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ data.nodes.maintenance }}</div>
              <div class="text-xs text-gray-500">Maintenance</div>
            </div>
          </div>
        </div>

        <!-- Top Targets -->
        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Top Targets Today</h3>
          <div v-if="data.top_targets.length === 0" class="text-sm text-gray-600">No tests today</div>
          <div v-else class="space-y-2">
            <div
              v-for="t in data.top_targets"
              :key="t.target"
              class="flex items-center justify-between rounded bg-gray-100 px-3 py-2 dark:bg-gray-800"
            >
              <span class="font-mono text-sm text-gray-900 dark:text-white">{{ t.target }}</span>
              <span class="text-sm text-gray-500 dark:text-gray-400">{{ t.test_count }} tests</span>
            </div>
          </div>
        </div>
      </div>

      <!-- System Information & Actions -->
      <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div v-if="sysInfo" class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">System Information</h3>
          <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div><dt class="text-xs text-gray-500">Version</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.version }}</dd></div>
            <div><dt class="text-xs text-gray-500">Environment</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.environment }}</dd></div>
            <div><dt class="text-xs text-gray-500">PHP Version</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.php_version }}</dd></div>
            <div><dt class="text-xs text-gray-500">Laravel Version</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.laravel_version }}</dd></div>
            <div><dt class="text-xs text-gray-500">Database</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.database_driver }}</dd></div>
            <div><dt class="text-xs text-gray-500">Queue</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.queue_connection }}</dd></div>
            <div><dt class="text-xs text-gray-500">Cache</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.cache_driver }}</dd></div>
            <div><dt class="text-xs text-gray-500">Total Tests</dt><dd class="font-mono text-sm text-gray-900 dark:text-white">{{ sysInfo.tests_total }}</dd></div>
          </dl>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</h3>
          <div class="flex gap-3">
            <button
              :disabled="migrating"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:border-gray-400 hover:text-gray-900 disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white"
              @click="runMigrations"
            >
              {{ migrating ? 'Running...' : 'Run Migrations' }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
