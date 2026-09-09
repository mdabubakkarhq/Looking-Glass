<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useNodesStore } from '@/stores/nodes'
import { useTestStore } from '@/stores/test'
import type { Node } from '@/types'
import StatusBadge from '@/components/common/StatusBadge.vue'
import CountryFlag from '@/components/common/CountryFlag.vue'

const nodesStore = useNodesStore()
const testStore = useTestStore()

const REFRESH_COOLDOWN_MS = 15_000
const lastRefreshTime = ref(0)
const now = ref(Date.now())

// Update "now" every 30s for freshness text
onMounted(() => {
  setInterval(() => { now.value = Date.now() }, 30_000)
  if (nodesStore.nodes.length > 0 && !nodesStore.probeRunning && !nodesStore.probeComplete) {
    void nodesStore.startProbe()
    lastRefreshTime.value = Date.now()
  }
})

// Start probe when nodes become available (handles race with async loadNodes in App.vue)
watch(
  () => nodesStore.nodes.length,
  (len) => {
    if (len > 0 && !nodesStore.probeRunning && !nodesStore.probeComplete) {
      void nodesStore.startProbe()
      lastRefreshTime.value = Date.now()
    }
  },
)

/** Find the lowest-latency completed node */
const bestNodeSlug = computed<string | null>(() => {
  let best: { slug: string; avg: number } | null = null
  for (const r of nodesStore.probeResults) {
    if (r.status === 'completed' && r.latency_avg_ms != null && (r.packet_loss_percent ?? 0) < 100) {
      if (!best || r.latency_avg_ms < best.avg) {
        best = { slug: r.node_slug, avg: r.latency_avg_ms }
      }
    }
  }
  return best?.slug ?? null
})

/** Sort nodes by measured latency ascending after probes complete */
const sortedNodes = computed<Node[]>(() => {
  if (!nodesStore.probeComplete && nodesStore.probeResults.length === 0) return nodesStore.nodes
  const latencyMap = new Map<string, number>()
  for (const r of nodesStore.probeResults) {
    if (r.status === 'completed' && r.latency_avg_ms != null && (r.packet_loss_percent ?? 0) < 100) {
      latencyMap.set(r.node_slug, r.latency_avg_ms)
    }
  }
  return [...nodesStore.nodes].sort((a, b) => {
    const aLat = latencyMap.get(a.id), bLat = latencyMap.get(b.id)
    if (aLat != null && bLat != null) return aLat - bLat
    if (aLat != null) return -1
    if (bLat != null) return 1
    return 0
  })
})

/** Format a latency result for display */
function latencyDisplay(slug: string): { text: string; class: string } | null {
  const r = nodesStore.getProbeResultForNode(slug)
  if (!r) return null
  if (r.status === 'measuring' || r.status === 'pending') {
    return { text: 'Measuring…', class: 'text-gray-500 dark:text-gray-400 animate-pulse' }
  }
  if (r.status === 'completed') {
    const avg = r.latency_avg_ms != null ? Math.round(r.latency_avg_ms) : null
    const loss = r.packet_loss_percent != null ? r.packet_loss_percent : 0
    if (avg != null && loss < 100) {
      const suffix = loss > 0 ? ` · ${Math.round(loss)}% loss` : ''
      return { text: `${avg} ms${suffix}`, class: 'text-green-700 dark:text-green-400' }
    }
    return { text: `Partial — ${Math.round(loss)}% loss`, class: 'text-yellow-600 dark:text-yellow-400' }
  }
  if (r.status === 'timeout' || r.status === 'blocked') {
    return { text: 'ICMP blocked or timed out', class: 'text-red-500 dark:text-red-400' }
  }
  if (r.status === 'error') {
    if (r.error_message?.toLowerCase().includes('rate limit')) {
      return { text: 'Rate limited — retry later', class: 'text-orange-500 dark:text-orange-400' }
    }
    return { text: r.error_message || 'Node unavailable', class: 'text-red-500 dark:text-red-400' }
  }
  return null
}

/** Tooltip detail text for completed latency results */
function latencyDetails(slug: string): string | null {
  const r = nodesStore.getProbeResultForNode(slug)
  if (!r || r.status !== 'completed') return null
  const parts: string[] = []
  if (r.latency_min_ms != null) parts.push(`Min: ${Math.round(r.latency_min_ms)} ms`)
  if (r.latency_avg_ms != null) parts.push(`Avg: ${Math.round(r.latency_avg_ms)} ms`)
  if (r.latency_max_ms != null) parts.push(`Max: ${Math.round(r.latency_max_ms)} ms`)
  if (r.jitter_ms != null) parts.push(`Jitter: ${Math.round(r.jitter_ms)} ms`)
  if (r.packet_loss_percent != null) parts.push(`Loss: ${Math.round(r.packet_loss_percent)}%`)
  if (r.ip_family) parts.push(`Protocol: ${r.ip_family}`)
  if (r.completed_at) parts.push(`Measured: ${freshnessText(r.completed_at)}`)
  return parts.join(' · ')
}

/** Human-readable freshness from an ISO timestamp */
function freshnessText(isoDate: string): string {
  const ms = now.value - new Date(isoDate).getTime()
  if (ms < 10_000) return 'just now'
  const seconds = Math.floor(ms / 1000)
  if (seconds < 60) return `${seconds}s ago`
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  return `${Math.floor(minutes / 60)}h ago`
}

const refreshCooldownActive = computed(() => Date.now() - lastRefreshTime.value < REFRESH_COOLDOWN_MS)
const refreshCooldownSeconds = computed(() => Math.max(0, Math.ceil((REFRESH_COOLDOWN_MS - (Date.now() - lastRefreshTime.value)) / 1000)))

function handleRefresh() {
  if (refreshCooldownActive.value || nodesStore.probeRunning) return
  lastRefreshTime.value = Date.now()
  void nodesStore.startProbe()
}

function selectNode(slug: string) {
  testStore.selectedNode = slug
}
</script>

<template>
  <div class="space-y-2" role="listbox" aria-label="Available locations">
    <!-- Refresh latency button -->
    <div class="flex justify-end">
      <button
        v-if="!nodesStore.probeRunning && !refreshCooldownActive"
        class="rounded border border-gray-300 px-2 py-1 text-[10px] text-gray-500 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-200"
        @click="handleRefresh"
        aria-label="Refresh latency measurements"
      >
        Refresh latency
      </button>
      <span v-else-if="nodesStore.probeRunning" class="text-[10px] text-gray-400 dark:text-gray-500 animate-pulse">
        Measuring all nodes…
      </span>
      <span v-else class="text-[10px] text-gray-400 dark:text-gray-500">
        Refresh in {{ refreshCooldownSeconds }}s
      </span>
    </div>

    <div
      v-for="node in sortedNodes"
      :key="node.id"
      class="cursor-pointer rounded-lg border p-3 transition-all"
      :class="[
        testStore.selectedNode === node.id
          ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
          : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-gray-600',
        bestNodeSlug === node.id && nodesStore.probeComplete ? 'ring-1 ring-green-400 dark:ring-green-600' : ''
      ]"
      role="option"
      :aria-selected="testStore.selectedNode === node.id"
      tabindex="0"
      @click="selectNode(node.id)"
      @keydown.enter.prevent="selectNode(node.id)"
      @keydown.space.prevent="selectNode(node.id)"
    >
      <!-- Row 1: Flag + Name + Best badge + Status -->
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0">
          <CountryFlag :code="node.country_code" class="shrink-0" />
          <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ node.name }}</span>
          <span
            v-if="bestNodeSlug === node.id && nodesStore.probeComplete"
            class="inline-flex items-center gap-0.5 rounded-full bg-green-100 px-1.5 py-0.5 text-[9px] font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300"
            aria-label="Best latency"
          >⚡ Best</span>
        </div>
        <StatusBadge :status="node.status" />
      </div>

      <!-- Row 2: City, Provider, Hostname -->
      <div class="mt-1 pl-7 text-xs text-gray-500 dark:text-gray-400 truncate">
        {{ node.city }}
        <span v-if="node.provider" class="text-gray-400 dark:text-gray-500">· {{ node.provider }}</span>
      </div>
      <div v-if="node.hostname" class="mt-0.5 pl-7 text-xs text-gray-400 dark:text-gray-500 truncate font-mono">
        {{ node.hostname }}
      </div>

      <!-- Row 3: Latency result with tooltip and freshness -->
      <div v-if="latencyDisplay(node.id)" class="mt-1.5 pl-7 flex items-center gap-1.5">
        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase">Latency</span>
        <span
          class="text-xs font-medium"
          :class="latencyDisplay(node.id)!.class"
          :title="latencyDetails(node.id) || undefined"
        >{{ latencyDisplay(node.id)!.text }}</span>
        <span
          v-if="latencyDisplay(node.id)?.class.includes('green') && nodesStore.getProbeResultForNode(node.id)?.completed_at"
          class="text-[9px] text-gray-400 dark:text-gray-500"
        >{{ freshnessText(nodesStore.getProbeResultForNode(node.id)!.completed_at!) }}</span>
      </div>
      <div v-else-if="nodesStore.probeRunning && node.latency_enabled" class="mt-1.5 pl-7 flex items-center gap-1.5">
        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase">Latency</span>
        <span class="text-xs text-gray-400 dark:text-gray-500 animate-pulse">Measuring…</span>
      </div>
      <div v-else-if="!node.latency_enabled" class="mt-1.5 pl-7">
        <span class="text-[10px] font-medium text-gray-400 dark:text-gray-600 uppercase">Latency</span>
        <span class="text-[10px] text-gray-400 dark:text-gray-600 ml-1">N/A</span>
      </div>

      <!-- Row 4: IPv4 / IPv6 addresses -->
      <div class="mt-1.5 pl-7 flex flex-wrap items-center gap-x-3 gap-y-1">
        <span v-if="node.ipv4" class="inline-flex items-center gap-1 text-xs">
          <span class="rounded border px-1 py-0.5 text-[10px] font-bold uppercase" :class="node.ipv4_enabled ? 'border-green-300 bg-green-100 text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-400' : 'border-gray-300 bg-gray-200 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500'">IPv4</span>
          <span class="font-mono text-gray-700 dark:text-gray-300">{{ node.ipv4 }}</span>
        </span>
        <span v-if="node.ipv6" class="inline-flex items-center gap-1 text-xs">
          <span class="rounded border px-1 py-0.5 text-[10px] font-bold uppercase" :class="node.ipv6_enabled ? 'border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-400' : 'border-gray-300 bg-gray-200 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500'">IPv6</span>
          <span class="font-mono text-gray-700 dark:text-gray-300">{{ node.ipv6 }}</span>
        </span>
        <span v-if="!node.ipv4 && !node.ipv6" class="text-xs text-gray-400 dark:text-gray-600">No IPs configured</span>
      </div>
    </div>

    <div v-if="nodesStore.nodes.length === 0 && !nodesStore.loading" class="py-8 text-center text-sm text-gray-500">
      No locations available
    </div>
  </div>
</template>
