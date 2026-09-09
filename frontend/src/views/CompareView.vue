<script setup lang="ts">
import { useNodesStore } from '@/stores/nodes'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import ComparisonForm from '@/components/comparison/ComparisonForm.vue'
import ComparisonTable from '@/components/comparison/ComparisonTable.vue'

const nodesStore = useNodesStore()
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Multi-Location Comparison</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        Select multiple nodes and run the same test from several regions simultaneously.
      </p>
    </div>

    <div v-if="nodesStore.loading && nodesStore.nodes.length === 0" class="py-16" role="status" aria-live="polite">
      <LoadingSpinner size="lg" label="Loading nodes..." />
    </div>

    <div
      v-else-if="nodesStore.error"
      role="alert"
      aria-live="polite"
      class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
    >
      <div class="flex items-center justify-between">
        <span>{{ nodesStore.error }}</span>
        <button
          class="ml-4 shrink-0 rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-200 dark:bg-red-900/40 dark:text-red-400 dark:hover:bg-red-900/60"
          @click="nodesStore.loadNodes()"
        >
          Retry
        </button>
      </div>
    </div>

    <div v-else class="space-y-8">
      <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800/30 sm:p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Configure Comparison</h2>
        <ComparisonForm />
      </div>

      <ComparisonTable />
    </div>
  </div>
</template>
