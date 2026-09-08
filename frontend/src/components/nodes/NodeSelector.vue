<script setup lang="ts">
import { useNodesStore } from '@/stores/nodes'
import { useTestStore } from '@/stores/test'
import StatusBadge from '@/components/common/StatusBadge.vue'

const nodesStore = useNodesStore()
const testStore = useTestStore()

function selectNode(slug: string) {
  testStore.selectedNode = slug
}
</script>

<template>
  <div class="space-y-2">
    <div
      v-for="node in nodesStore.nodes"
      :key="node.id"
      class="cursor-pointer rounded-lg border p-3 transition-all"
      :class="
        testStore.selectedNode === node.id
          ? 'border-primary-500 bg-primary-900/20'
          : 'border-gray-700 bg-gray-800/50 hover:border-gray-600'
      "
      @click="selectNode(node.id)"
    >
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-white">{{ node.name }}</span>
            <StatusBadge :status="node.status" />
          </div>
          <div class="mt-0.5 text-xs text-gray-400">
            {{ node.location }}
            <span v-if="node.provider" class="text-gray-500">&middot; {{ node.provider }}</span>
          </div>
        </div>
        <div class="text-right">
          <div v-if="node.asn" class="text-xs font-mono text-gray-500">{{ node.asn }}</div>
          <div class="mt-1 flex flex-wrap justify-end gap-1">
            <span
              v-for="cap in node.capabilities"
              :key="cap"
              class="rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-400"
            >
              {{ cap }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
