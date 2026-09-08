<script setup lang="ts">
import { useNodesStore } from '@/stores/nodes'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import TestForm from '@/components/test/TestForm.vue'
import TestOutput from '@/components/test/TestOutput.vue'
import TestResults from '@/components/test/TestResults.vue'
import NodeSelector from '@/components/nodes/NodeSelector.vue'

const nodesStore = useNodesStore()
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-white sm:text-3xl">Looking Glass</h1>
      <p class="mt-2 text-gray-400">
        Run network diagnostic tests (Ping, Traceroute, MTR, DNS) from our global node locations.
      </p>
    </div>

    <!-- Loading -->
    <div v-if="nodesStore.loading && nodesStore.nodes.length === 0" class="py-16">
      <LoadingSpinner size="lg" label="Loading nodes..." />
    </div>

    <!-- Error -->
    <div
      v-else-if="nodesStore.error"
      class="rounded-lg border border-red-800 bg-red-900/20 px-4 py-3 text-red-400"
    >
      {{ nodesStore.error }}
    </div>

    <!-- Main Content -->
    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <!-- Left Sidebar: Node Selector -->
      <aside class="lg:col-span-4 xl:col-span-3">
        <div class="sticky top-24">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">
            Select Node
          </h2>
          <NodeSelector />
        </div>
      </aside>

      <!-- Main Panel: Test Form + Output -->
      <div class="space-y-6 lg:col-span-8 xl:col-span-9">
        <!-- Test Form -->
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-4 sm:p-6">
          <h2 class="mb-4 text-lg font-semibold text-white">Run Test</h2>
          <TestForm />
        </div>

        <!-- Test Output -->
        <TestOutput />

        <!-- Test Results -->
        <TestResults />
      </div>
    </div>
  </div>
</template>
