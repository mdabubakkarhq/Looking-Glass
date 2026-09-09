<script setup lang="ts">
import type { NodeStatus, TestStatus } from '@/types'

defineProps<{
  status: NodeStatus | TestStatus
}>()

function statusConfig(status: string) {
  const configs: Record<string, { bg: string; text: string; dot: string; label: string }> = {
    online: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', dot: 'bg-green-500 dark:bg-green-400 animate-pulse-online', label: 'Online' },
    offline: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', dot: 'bg-red-500 dark:bg-red-400', label: 'Offline' },
    maintenance: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-400', dot: 'bg-yellow-500 dark:bg-yellow-400', label: 'Maintenance' },
    pending: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-400', dot: 'bg-blue-500 dark:bg-blue-400 animate-pulse', label: 'Pending' },
    running: { bg: 'bg-primary-100 dark:bg-primary-900/30', text: 'text-primary-700 dark:text-primary-400', dot: 'bg-primary-500 dark:bg-primary-400 animate-pulse', label: 'Running' },
    completed: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', dot: 'bg-green-500 dark:bg-green-400', label: 'Completed' },
    failed: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', dot: 'bg-red-500 dark:bg-red-400', label: 'Failed' },
  }
  return configs[status] ?? configs.offline!
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="statusConfig(status).bg + ' ' + statusConfig(status).text"
  >
    <span
      class="inline-block h-1.5 w-1.5 rounded-full"
      :class="statusConfig(status).dot"
    />
    {{ statusConfig(status).label }}
  </span>
</template>
