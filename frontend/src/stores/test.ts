import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'
import type {
  IpFamily,
  TestCreateRequest,
  TestCreateResponse,
  TestResult,
  TestStatus,
  TestType,
} from '@/types'

export interface OutputLine {
  text: string
  timestamp: number
}

export const useTestStore = defineStore('test', () => {
  const currentTest = ref<TestCreateResponse | null>(null)
  const testResult = ref<TestResult | null>(null)
  const outputLines = ref<OutputLine[]>([])
  const testStatus = ref<TestStatus | null>(null)
  const submitting = ref(false)
  const streaming = ref(false)
  const error = ref<string | null>(null)

  const selectedNode = ref<string>('')
  const selectedTestType = ref<TestType>('ping')
  const target = ref<string>('')
  const selectedIpFamily = ref<IpFamily>('auto')
  const testHistory = ref<TestCreateResponse[]>([])

  let eventSource: EventSource | null = null

  const isActive = computed(() =>
    testStatus.value === 'pending' || testStatus.value === 'running',
  )

  const hasResult = computed(() =>
    testStatus.value === 'completed' || testStatus.value === 'failed',
  )

  async function submitTest(): Promise<void> {
    if (!selectedNode.value || !target.value.trim()) return
    submitting.value = true
    error.value = null
    outputLines.value = []
    testResult.value = null
    testStatus.value = null

    const request: TestCreateRequest = {
      node_id: selectedNode.value,
      test_type: selectedTestType.value,
      target: target.value.trim(),
      ip_family: selectedIpFamily.value,
    }

    try {
      const response = await api.createTest(request)
      currentTest.value = response.data
      testStatus.value = response.data.status
      testHistory.value.unshift(response.data)
      connectStream(response.data.id)
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to submit test'
      testStatus.value = 'failed'
    } finally {
      submitting.value = false
    }
  }

  async function fetchTestResult(testId: string): Promise<void> {
    try {
      const response = await api.getTest(testId)
      testResult.value = response.data
    } catch (err) {
      console.error('Failed to fetch test result:', err)
    }
  }

  function connectStream(testId: string): void {
    disconnectStream()
    const url = api.getTestStreamUrl(testId)
    eventSource = new EventSource(url)
    streaming.value = true

    eventSource.addEventListener('test.started', () => {
      testStatus.value = 'running'
    })

    eventSource.addEventListener('test.output', (event: MessageEvent) => {
      try {
        const data = JSON.parse(event.data) as { line: string; timestamp: string }
        outputLines.value.push({
          text: data.line,
          timestamp: new Date(data.timestamp).getTime(),
        })
      } catch {
        outputLines.value.push({ text: event.data, timestamp: Date.now() })
      }
    })

    eventSource.addEventListener('test.completed', (event: MessageEvent) => {
      testStatus.value = 'completed'
      streaming.value = false
      try {
        const stats = JSON.parse(event.data) as Partial<TestResult>
        if (testResult.value) Object.assign(testResult.value, stats)
        else testResult.value = stats as TestResult
      } catch { /* ignore parse errors */ }
      void fetchTestResult(testId)
      disconnectStream()
    })

    eventSource.addEventListener('test.failed', (event: MessageEvent) => {
      testStatus.value = 'failed'
      streaming.value = false
      try {
        const data = JSON.parse(event.data) as { error_code: string; error_message: string }
        error.value = data.error_message || data.error_code
      } catch {
        error.value = event.data || 'Test failed'
      }
      void fetchTestResult(testId)
      disconnectStream()
    })

    eventSource.onerror = () => {
      if (isActive.value) {
        streaming.value = false
        setTimeout(() => {
          if (eventSource?.readyState === EventSource.CLOSED) {
            testStatus.value = 'failed'
            error.value = 'Stream connection lost'
          }
        }, 5000)
      } else {
        streaming.value = false
      }
    }
  }

  function disconnectStream(): void {
    if (eventSource) {
      eventSource.close()
      eventSource = null
    }
    streaming.value = false
  }

  function resetTest(): void {
    disconnectStream()
    currentTest.value = null
    testResult.value = null
    outputLines.value = []
    testStatus.value = null
    error.value = null
  }

  function resetAll(): void {
    resetTest()
    selectedNode.value = ''
    selectedTestType.value = 'ping'
    target.value = ''
    selectedIpFamily.value = 'auto'
    testHistory.value = []
  }

  return {
    currentTest, testResult, outputLines, testStatus,
    submitting, streaming, error,
    selectedNode, selectedTestType, target, selectedIpFamily,
    testHistory, isActive, hasResult,
    submitTest, connectStream, disconnectStream,
    fetchTestResult, resetTest, resetAll,
  }
})
