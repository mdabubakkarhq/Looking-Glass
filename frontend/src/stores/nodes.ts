import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'
import type { LatencyEntry, Node } from '@/types'

export const useNodesStore = defineStore('nodes', () => {
  const nodes = ref<Node[]>([])
  const latency = ref<LatencyEntry[]>([])
  const loading = ref(false)
  const latencyLoading = ref(false)
  const error = ref<string | null>(null)
  const lastLatencyFetch = ref<number>(0)

  const onlineNodes = computed(() => nodes.value.filter(n => n.status === 'online'))
  const offlineNodes = computed(() => nodes.value.filter(n => n.status === 'offline'))
  const nodeCount = computed(() => nodes.value.length)

  function getNodeBySlug(slug: string): Node | undefined {
    return nodes.value.find(n => n.id === slug)
  }

  function getLatencyForNode(slug: string): LatencyEntry | undefined {
    return latency.value.find(l => l.node_id === slug)
  }

  async function loadNodes(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await api.getNodes()
      nodes.value = response.data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load nodes'
      console.error('Failed to load nodes:', err)
    } finally {
      loading.value = false
    }
  }

  async function loadLatency(force = false): Promise<void> {
    // Cache for 15 seconds
    if (!force && Date.now() - lastLatencyFetch.value < 15_000) return

    latencyLoading.value = true

    try {
      const response = await api.getLatency()
      latency.value = response.data
      lastLatencyFetch.value = Date.now()
    } catch (err) {
      console.error('Failed to load latency:', err)
    } finally {
      latencyLoading.value = false
    }
  }

  return {
    nodes,
    latency,
    loading,
    latencyLoading,
    error,
    onlineNodes,
    offlineNodes,
    nodeCount,
    getNodeBySlug,
    getLatencyForNode,
    loadNodes,
    loadLatency,
  }
})
