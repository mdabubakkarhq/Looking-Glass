import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'
import type { TestCreateResponse, TestResult, TestType, IpFamily } from '@/types'

export interface ComparisonEntry {
  nodeId: string
  nodeName: string
  testId: string | null
  status: 'idle' | 'pending' | 'running' | 'completed' | 'failed'
  outputLines: string[]
  result: TestResult | null
  error: string | null
  eventSource: EventSource | null
}

export const useComparisonStore = defineStore('comparison', () => {
  const selectedNodes = ref<Set<string>>(new Set())
  const testType = ref<TestType>('ping')
  const target = ref('')
  const ipFamily = ref<IpFamily>('auto')
  const maxNodes = 5

  const entries = ref<Map<string, ComparisonEntry>>(new Map())
  const submitting = ref(false)
  const globalError = ref<string | null>(null)

  const isRunning = computed(() => {
    for (const entry of entries.value.values()) {
      if (entry.status === 'pending' || entry.status === 'running') return true
    }
    return false
  })

  const allCompleted = computed(() => {
    if (entries.value.size === 0) return false
    for (const entry of entries.value.values()) {
      if (entry.status !== 'completed' && entry.status !== 'failed') return false
    }
    return true
  })

  const hasResults = computed(() => entries.value.size > 0)

  function toggleNode(nodeId: string): void {
    const set = new Set(selectedNodes.value)
    if (set.has(nodeId)) {
      set.delete(nodeId)
    } else {
      if (set.size >= maxNodes) return
      set.add(nodeId)
    }
    selectedNodes.value = set
  }

  function selectAllNodeIds(nodeIds: string[]): void {
    selectedNodes.value = new Set(nodeIds.slice(0, maxNodes))
  }

  function clearSelection(): void {
    selectedNodes.value = new Set()
  }

  async function runComparison(
    nodes: Array<{ id: string; name: string }>,
  ): Promise<void> {
    if (selectedNodes.value.size === 0 || !target.value.trim()) return

    submitting.value = true
    globalError.value = null
    disconnectAll()

    const newEntries = new Map<string, ComparisonEntry>()
    for (const node of nodes) {
      if (selectedNodes.value.has(node.id)) {
        newEntries.set(node.id, {
          nodeId: node.id, nodeName: node.name, testId: null,
          status: 'idle', outputLines: [], result: null, error: null, eventSource: null,
        })
      }
    }
    entries.value = newEntries

    const promises: Promise<void>[] = []
    for (const node of nodes) {
      if (!selectedNodes.value.has(node.id)) continue
      promises.push(submitSingleNode(node.id, node.name))
    }

    try {
      await Promise.allSettled(promises)
    } finally {
      submitting.value = false
    }
  }

  async function submitSingleNode(nodeId: string, _nodeName: string): Promise<void> {
    const entry = entries.value.get(nodeId)
    if (!entry) return

    entry.status = 'pending'
    entry.error = null

    try {
      const response = await api.createTest({
        node_id: nodeId, test_type: testType.value,
        target: target.value.trim(), ip_family: ipFamily.value,
      })
      const testData: TestCreateResponse = response.data
      entry.testId = testData.id
      entry.status = 'running'
      connectStream(nodeId, testData.id)
    } catch (err) {
      entry.status = 'failed'
      entry.error = err instanceof Error ? err.message : 'Failed to submit test'
    }
  }

  function connectStream(nodeId: string, testId: string): void {
    const entry = entries.value.get(nodeId)
    if (!entry) return

    const es = new EventSource(api.getTestStreamUrl(testId))
    entry.eventSource = es

    es.addEventListener('test.started', () => { entry.status = 'running' })

    es.addEventListener('test.output', (event: MessageEvent) => {
      try {
        const data = JSON.parse(event.data) as { line: string }
        entry.outputLines.push(data.line)
      } catch { entry.outputLines.push(event.data) }
    })

    es.addEventListener('test.completed', (event: MessageEvent) => {
      entry.status = 'completed'
      try {
        const stats = JSON.parse(event.data) as Partial<TestResult>
        if (entry.result) Object.assign(entry.result, stats)
        else entry.result = stats as TestResult
      } catch { /* ignore */ }
      void fetchResult(nodeId, testId)
      disconnectNode(nodeId)
    })

    es.addEventListener('test.failed', (event: MessageEvent) => {
      entry.status = 'failed'
      try {
        const data = JSON.parse(event.data) as { error_message: string }
        entry.error = data.error_message || 'Test failed'
      } catch { entry.error = event.data || 'Test failed' }
      disconnectNode(nodeId)
    })

    es.onerror = () => {
      if (entry.status === 'running' || entry.status === 'pending') {
        setTimeout(() => {
          if (es.readyState === EventSource.CLOSED) {
            entry.status = 'failed'
            entry.error = 'Stream connection lost'
            disconnectNode(nodeId)
          }
        }, 5000)
      }
    }
  }

  async function fetchResult(nodeId: string, testId: string): Promise<void> {
    try {
      const response = await api.getTest(testId)
      const entry = entries.value.get(nodeId)
      if (entry) entry.result = response.data
    } catch { /* silently ignore */ }
  }

  function disconnectNode(nodeId: string): void {
    const entry = entries.value.get(nodeId)
    if (entry?.eventSource) { entry.eventSource.close(); entry.eventSource = null }
  }

  function disconnectAll(): void {
    for (const nodeId of entries.value.keys()) disconnectNode(nodeId)
  }

  function reset(): void {
    disconnectAll()
    entries.value = new Map()
    globalError.value = null
  }

  return {
    selectedNodes, testType, target, ipFamily, maxNodes,
    entries, submitting, globalError,
    isRunning, allCompleted, hasResults,
    toggleNode, selectAllNodeIds, clearSelection,
    runComparison, disconnectAll, reset,
  }
})
