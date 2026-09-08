<script setup lang="ts">
import { onMounted } from 'vue'
import { useNodesStore } from '@/stores/nodes'
import { useAppStore } from '@/stores/app'
import LatencyTable from '@/components/latency/LatencyTable.vue'

const nodesStore = useNodesStore()
const appStore = useAppStore()

onMounted(() => {
  void nodesStore.loadLatency()
})
</script>

<template>
  <footer class="border-t border-gray-800 bg-gray-950">
    <!-- Automatic Latency Table -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
      <LatencyTable />
    </div>

    <!-- Footer Info -->
    <div class="border-t border-gray-800">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:flex-row sm:px-6">
        <div class="text-sm text-gray-500">
          {{ appStore.getSiteName() }}
          <span v-if="appStore.getOrganization()">
            &middot; {{ appStore.getOrganization() }}
          </span>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-500">
          <span>Powered by Open Looking Glass</span>
        </div>
      </div>
    </div>
  </footer>
</template>
