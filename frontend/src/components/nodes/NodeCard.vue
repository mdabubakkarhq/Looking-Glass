<script setup lang="ts">
import type { Node } from '@/types'
import { useNodesStore } from '@/stores/nodes'
import StatusBadge from '@/components/common/StatusBadge.vue'

const props = defineProps<{
  node: Node
}>()

const nodesStore = useNodesStore()

function formatLatency(ms: number | null): string {
  if (ms === null || ms === undefined) return '—'
  return ms < 1 ? '<1 ms' : `${Math.round(ms)} ms`
}

function latencyColorClass(ms: number | null): string {
  if (ms === null) return 'text-gray-500'
  if (ms < 20) return 'text-green-400'
  if (ms < 50) return 'text-emerald-400'
  if (ms < 100) return 'text-yellow-400'
  if (ms < 200) return 'text-orange-400'
  return 'text-red-400'
}

const latency = nodesStore.getLatencyForNode(props.node.id)
</script>

<template>
  <div class="rounded-lg border border-gray-700 bg-gray-800/50 p-4 transition-colors hover:border-gray-600">
    <div class="flex items-start justify-between">
      <div>
        <h3 class="text-base font-semibold text-white">{{ node.name }}</h3>
        <p class="mt-0.5 text-sm text-gray-400">{{ node.location }}</p>
      </div>
      <StatusBadge :status="node.status" />
    </div>

    <div class="mt-4 space-y-2 text-sm">
      <div v-if="node.provider" class="flex justify-between">
        <span class="text-gray-500">Provider</span>
        <span class="text-gray-300">{{ node.provider }}</span>
      </div>
      <div v-if="node.asn" class="flex justify-between">
        <span class="text-gray-500">ASN</span>
        <span class="font-mono text-gray-300">{{ node.asn }}</span>
      </div>
      <div v-if="node.ipv4" class="flex justify-between">
        <span class="text-gray-500">IPv4</span>
        <span class="font-mono text-gray-300">{{ node.ipv4 }}</span>
      </div>
      <div v-if="node.ipv6" class="flex justify-between">
        <span class="text-gray-500">IPv6</span>
        <span class="font-mono text-xs text-gray-300">{{ node.ipv6 }}</span>
      </div>
      <div v-if="node.uplink_mbps" class="flex justify-between">
        <span class="text-gray-500">Uplink</span>
        <span class="text-gray-300">{{ node.uplink_mbps }} Mbps</span>
      </div>
      <div v-if="latency" class="flex justify-between">
        <span class="text-gray-500">Latency</span>
        <span class="font-mono font-medium" :class="latencyColorClass(latency.latency_ms)">
          {{ formatLatency(latency.latency_ms) }}
        </span>
      </div>
    </div>

    <!-- Capabilities -->
    <div v-if="node.capabilities.length" class="mt-4 flex flex-wrap gap-1.5">
      <span
        v-for="cap in node.capabilities"
        :key="cap"
        class="rounded-md bg-gray-700 px-2 py-0.5 text-xs font-medium uppercase text-gray-400"
      >
        {{ cap }}
      </span>
    </div>
  </div>
</template>
