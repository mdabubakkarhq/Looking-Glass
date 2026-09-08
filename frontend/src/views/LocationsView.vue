<script setup lang="ts">
import { onMounted } from 'vue'
import { useNodesStore } from '@/stores/nodes'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import NodeCard from '@/components/nodes/NodeCard.vue'

const nodesStore = useNodesStore()

onMounted(() => {
  void nodesStore.loadLatency()
})
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-white sm:text-3xl">Locations</h1>
      <p class="mt-2 text-gray-400">
        Our global network of {{ nodesStore.nodeCount }} node{{ nodesStore.nodeCount !== 1 ? 's' : '' }} provides
        network diagnostic coverage across multiple regions.
      </p>
    </div>

    <!-- Node Stats -->
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-4">
        <div class="text-2xl font-bold text-white">{{ nodesStore.nodeCount }}</div>
        <div class="text-sm text-gray-500">Total Nodes</div>
      </div>
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-4">
        <div class="text-2xl font-bold text-green-400">{{ nodesStore.onlineNodes.length }}</div>
        <div class="text-sm text-gray-500">Online</div>
      </div>
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-4">
        <div class="text-2xl font-bold text-red-400">{{ nodesStore.offlineNodes.length }}</div>
        <div class="text-sm text-gray-500">Offline</div>
      </div>
      <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-4">
        <div class="text-2xl font-bold text-gray-400">
          {{ nodesStore.nodes.length - nodesStore.onlineNodes.length - nodesStore.offlineNodes.length }}
        </div>
        <div class="text-sm text-gray-500">Other</div>
      </div>
    </div>

    <div v-if="nodesStore.loading && nodesStore.nodes.length === 0" class="py-16">
      <LoadingSpinner size="lg" label="Loading locations..." />
    </div>

    <div v-else-if="nodesStore.nodes.length === 0" class="py-16 text-center text-gray-500">
      No nodes are currently configured.
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <NodeCard
        v-for="node in nodesStore.nodes"
        :key="node.id"
        :node="node"
      />
    </div>
  </div>
</template>
