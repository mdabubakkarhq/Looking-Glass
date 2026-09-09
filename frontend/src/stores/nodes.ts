import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'
import type { DownloadFile, LatencyEntry, LatencyProbeNodeResult, Node } from '@/types'

export const useNodesStore = defineStore('nodes', () => {
  const nodes = ref<Node[]>([])
  const latency = ref<LatencyEntry[]>([])
  const downloads = ref<DownloadFile[]>([])
  const loading = ref(false)
  const latencyLoading = ref(false)
  const downloadsLoading = ref(false)
  const error = ref<string | null>(null)
  const lastLatencyFetch = ref<number>(0)
  const downloadsLoaded = ref(false)

  // ── Latency Probe State ─────────────────────────────────────
  const probeSessionId = ref<string | null>(null)
  const probeResults = ref<LatencyProbeNodeResult[]>([])
  const probeRunning = ref(false)
  const probeComplete = ref(false)
  const probeError = ref<string | null>(null)
  let probePollTimer: ReturnType<typeof setInterval> | null = null

  const onlineNodes = computed(() => nodes.value.filter(n => n.status === 'online'))
  const offlineNodes = computed(() => nodes.value.filter(n => n.status === 'offline'))
  const nodeCount = computed(() => nodes.value.length)

  function getNodeBySlug(slug: string): Node | undefined {
    return nodes.value.find(n => n.id === slug)
  }

  function getLatencyForNode(slug: string): LatencyEntry | undefined {
    return latency.value.find(l => l.id === slug)
  }

  function getProbeResultForNode(slug: string): LatencyProbeNodeResult | undefined {
    return probeResults.value.find(r => r.node_slug === slug)
  }

  function getDownloadsForNode(slug: string): DownloadFile[] {
    return downloads.value.filter(d => d.node_id === slug)
  }

  async function loadNodes(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await api.getNodes()
      nodes.value = response.data
    } catch (err: unknown) {
      if (err instanceof Error && 'status' in err && (err as { status: number }).status === 429) {
        error.value = 'Too many requests. Please wait a moment and try again.'
      } else if (nodes.value.length > 0) {
        error.value = 'Unable to refresh node data. Showing previously loaded information.'
      } else {
        error.value = err instanceof Error ? err.message : 'Failed to load nodes'
      }
      console.error('Failed to load nodes:', err)
    } finally {
      loading.value = false
    }
  }

  async function loadLatency(force = false): Promise<void> {
    if (!force && Date.now() - lastLatencyFetch.value < 15_000) return

    latencyLoading.value = true

    try {
      const response = await api.getLatency()
      latency.value = response.nodes
      lastLatencyFetch.value = Date.now()
    } catch (err) {
      console.error('Failed to load latency:', err)
    } finally {
      latencyLoading.value = false
    }
  }

  async function loadDownloads(): Promise<void> {
    if (downloadsLoaded.value) return

    downloadsLoading.value = true

    try {
      const response = await api.getDownloads()
      downloads.value = response.data
      downloadsLoaded.value = true
    } catch (err) {
      console.error('Failed to load downloads:', err)
    } finally {
      downloadsLoading.value = false
    }
  }

  // ── Latency Probe ───────────────────────────────────────────

  async function startProbe(): Promise<void> {
    if (probeRunning.value) return

    probeRunning.value = true
    probeComplete.value = false
    probeError.value = null
    probeResults.value = []

    try {
      const response = await api.startLatencyProbe()
      probeSessionId.value = response.data.session_id

      // Start polling for results
      if (probePollTimer) clearInterval(probePollTimer)
      probePollTimer = setInterval(() => void pollProbeResults(), 2000)
      // Immediate first poll
      await pollProbeResults()
    } catch (err) {
      probeError.value = err instanceof Error ? err.message : 'Failed to start latency probe'
      probeRunning.value = false
    }
  }

  async function pollProbeResults(): Promise<void> {
    if (!probeSessionId.value) return

    try {
      const response = await api.getLatencyProbeResults(probeSessionId.value)
      probeResults.value = response.data.nodes

      if (response.data.complete) {
        probeComplete.value = true
        probeRunning.value = false
        if (probePollTimer) {
          clearInterval(probePollTimer)
          probePollTimer = null
        }
      }
    } catch (err) {
      console.error('Failed to poll probe results:', err)
    }
  }

  function stopProbe(): void {
    if (probePollTimer) {
      clearInterval(probePollTimer)
      probePollTimer = null
    }
    probeRunning.value = false
  }

  return {
    nodes,
    latency,
    downloads,
    loading,
    latencyLoading,
    downloadsLoading,
    error,
    onlineNodes,
    offlineNodes,
    nodeCount,
    probeSessionId,
    probeResults,
    probeRunning,
    probeComplete,
    probeError,
    getNodeBySlug,
    getLatencyForNode,
    getProbeResultForNode,
    getDownloadsForNode,
    loadNodes,
    loadLatency,
    loadDownloads,
    startProbe,
    stopProbe,
  }
})
