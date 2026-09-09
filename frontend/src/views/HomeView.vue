<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useNodesStore } from '@/stores/nodes'
import { useTestStore } from '@/stores/test'
import { useAppStore } from '@/stores/app'
import { api } from '@/api/client'
import type { TestType, WellKnownTarget, ConnectionInfo, DownloadFile } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import TestOutput from '@/components/test/TestOutput.vue'
import TestResults from '@/components/test/TestResults.vue'
import NodeSelector from '@/components/nodes/NodeSelector.vue'
import Iperf3Test from '@/components/nodes/Iperf3Test.vue'
import CountryFlag from '@/components/common/CountryFlag.vue'

const nodesStore = useNodesStore()
const testStore = useTestStore()
const appStore = useAppStore()

const customTargetInput = ref('')
const customTargetId = 'custom-target-input'
const showIperf3Panel = ref(false)

// ── Connection Info ─────────────────────────────────────────
const connectionInfo = ref<ConnectionInfo | null>(null)
const connectionLoading = ref(true)
const connectionError = ref<string | null>(null)

async function loadConnectionInfo(): Promise<void> {
  connectionLoading.value = true
  connectionError.value = null
  try {
    const response = await api.getConnection()
    connectionInfo.value = response.data
  } catch (err) {
    connectionError.value = err instanceof Error ? err.message : 'Failed to detect connection'
  } finally {
    connectionLoading.value = false
  }
}

onMounted(() => {
  void loadConnectionInfo()
})

/** Convert ISO country code to flag emoji */
function getCountryFlag(code: string): string {
  if (!code || code.length !== 2) return ''
  const base = 0x1F1E6
  const a = code.charCodeAt(0) - 65 + base
  const b = code.charCodeAt(1) - 65 + base
  return String.fromCodePoint(a) + String.fromCodePoint(b)
}

const selectedNodeObj = computed(() => {
  if (!testStore.selectedNode) return null
  return nodesStore.getNodeBySlug(testStore.selectedNode)
})

interface TestTarget {
  ip: string
  family: 'ipv4' | 'ipv6'
  label: string
  port: number
}

/** Default fallback targets used when admin hasn't configured any */
const defaultWellKnownTargets: WellKnownTarget[] = [
  { ip: '1.1.1.1', family: 'ipv4', port: 80, label: 'one.one.one.one' },
  { ip: '8.8.8.8', family: 'ipv4', port: 80, label: 'dns.google' },
  { ip: '2001:4860:4860::8888', family: 'ipv6', port: 80, label: 'dns.google' },
  { ip: '2606:4700:4700::1111', family: 'ipv6', port: 80, label: 'one.one.one.one' },
]

const wellKnownTargets = computed<WellKnownTarget[]>(() => {
  const fromConfig = appStore.config?.well_known_targets
  return Array.isArray(fromConfig) && fromConfig.length > 0 ? fromConfig : defaultWellKnownTargets
})

const testTargets = computed<TestTarget[]>(() => {
  if (!selectedNodeObj.value) return []
  const node = selectedNodeObj.value
  const targets: TestTarget[] = []

  if (node.ipv4) {
    targets.push({ ip: node.ipv4, family: 'ipv4', label: node.name, port: 80 })
  }
  if (node.ipv6) {
    targets.push({ ip: node.ipv6, family: 'ipv6', label: node.name, port: 80 })
  }

  for (const t of wellKnownTargets.value) {
    targets.push({ ip: t.ip, family: t.family, label: t.label, port: t.port })
  }

  return targets
})

function selectTarget(ip: string) {
  testStore.target = ip
}

function submitCustomTarget() {
  const val = customTargetInput.value.trim()
  if (!val) return
  testStore.target = val
}

function runTest(type: TestType) {
  if (!testStore.target.trim()) return
  testStore.selectedTestType = type
  void testStore.submitTest()
}

function testButtonClasses(type: TestType | 'iperf3'): string {
  const isActive = type !== 'iperf3' && testStore.selectedTestType === type && testStore.isActive
  const iperf3Active = type === 'iperf3' && showIperf3Panel.value
  const map: Record<string, { active: string; idle: string }> = {
    ping: {
      active: 'border-blue-500 bg-blue-600 text-white',
      idle: 'border-blue-300 bg-white text-blue-700 hover:bg-blue-50 hover:text-blue-800 dark:border-blue-700/60 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:border-blue-500 dark:hover:bg-blue-900/30 dark:hover:text-blue-300',
    },
    traceroute: {
      active: 'border-orange-500 bg-orange-600 text-white',
      idle: 'border-orange-300 bg-white text-orange-700 hover:bg-orange-50 hover:text-orange-800 dark:border-orange-700/60 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:border-orange-500 dark:hover:bg-orange-900/30 dark:hover:text-orange-300',
    },
    mtr: {
      active: 'border-violet-500 bg-violet-600 text-white',
      idle: 'border-violet-300 bg-white text-violet-700 hover:bg-violet-50 hover:text-violet-800 dark:border-violet-700/60 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:border-violet-500 dark:hover:bg-violet-900/30 dark:hover:text-violet-300',
    },
    dns: {
      active: 'border-cyan-500 bg-cyan-600 text-white',
      idle: 'border-cyan-300 bg-white text-cyan-700 hover:bg-cyan-50 hover:text-cyan-800 dark:border-cyan-700/60 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:border-cyan-500 dark:hover:bg-cyan-900/30 dark:hover:text-cyan-300',
    },
    iperf3: {
      active: 'border-purple-500 bg-purple-600 text-white',
      idle: 'border-purple-300 bg-white text-purple-700 hover:bg-purple-50 hover:text-purple-800 dark:border-purple-700/60 dark:bg-gray-700/60 dark:text-gray-200 dark:hover:border-purple-500 dark:hover:bg-purple-900/30 dark:hover:text-purple-300',
    },
  }
  const entry = map[type] ?? map.ping
  return (isActive || iperf3Active) ? entry!.active : entry!.idle
}

/** Color classes for download file cards (matches lg.metrovps.com palette) */
const downloadColorMap: Record<string, string> = {
  blue: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:border-blue-300 dark:border-blue-700 dark:bg-blue-900/10 dark:text-blue-300 dark:hover:bg-blue-900/20 dark:hover:border-blue-600',
  orange: 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100 hover:border-orange-300 dark:border-orange-700 dark:bg-orange-900/10 dark:text-orange-300 dark:hover:bg-orange-900/20 dark:hover:border-orange-600',
  purple: 'border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 hover:border-purple-300 dark:border-purple-700 dark:bg-purple-900/10 dark:text-purple-300 dark:hover:bg-purple-900/20 dark:hover:border-purple-600',
  rose: 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 hover:border-rose-300 dark:border-rose-700 dark:bg-rose-900/10 dark:text-rose-300 dark:hover:bg-rose-900/20 dark:hover:border-rose-600',
}
const downloadIconColors: Record<string, string> = {
  blue: 'text-blue-400',
  orange: 'text-orange-400',
  purple: 'text-purple-400',
  rose: 'text-rose-400',
}

function downloadFileColor(file: DownloadFile, allFiles: DownloadFile[]): string {
  if (allFiles.length <= 1) return 'emerald'
  const label = file.size_label.toUpperCase()
  if (label.includes('GB')) return 'purple'
  if (label.includes('100') && label.includes('MB')) return 'orange'
  if (label.includes('10') && label.includes('MB')) return 'rose'
  // 1MB or smallest file → blue
  return 'blue'
}

function downloadFileClasses(file: DownloadFile, allFiles: DownloadFile[]): string {
  const color = downloadFileColor(file, allFiles)
  if (color === 'emerald') {
    return 'inline-flex items-center gap-1.5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700 transition-colors hover:border-emerald-500 hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-700/60 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-200'
  }
  return `inline-flex items-center gap-1.5 rounded-md border px-4 py-2.5 text-sm font-medium transition-colors ${downloadColorMap[color]}`
}

watch(
  () => testStore.selectedNode,
  (slug) => {
    if (slug) void nodesStore.loadDownloads()
  },
  { immediate: true },
)
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Looking Glass</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        Network connectivity and performance testing from global locations.
      </p>
    </div>

    <!-- Loading -->
    <div v-if="nodesStore.loading && nodesStore.nodes.length === 0" class="py-16">
      <LoadingSpinner size="lg" label="Loading locations..." />
    </div>

    <!-- Error -->
    <div v-else-if="nodesStore.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
      <div class="flex items-center justify-between">
        <span>{{ nodesStore.error }}</span>
        <button
          class="ml-4 shrink-0 rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-200 dark:bg-red-900/40 dark:text-red-400 dark:hover:bg-red-900/60"
          @click="nodesStore.loadNodes()"
        >
          Retry
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">

      <!-- LEFT SIDEBAR -->
      <aside class="lg:col-span-4 xl:col-span-3">
        <div class="sticky top-24">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">
            Available Locations
          </h2>
          <NodeSelector />
        </div>
      </aside>

      <!-- MAIN PANEL -->
      <div class="space-y-6 lg:col-span-8 xl:col-span-9">

        <!-- Location Selector Dropdown -->
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/30 p-4 sm:p-6">
          <label for="location-select" class="mb-3 block text-base font-semibold text-gray-900 dark:text-white">Select Testing Location</label>
          <select
            id="location-select"
            v-model="testStore.selectedNode"
            aria-label="Select testing location"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          >
            <option value="" disabled>Choose a location...</option>
            <option
              v-for="node in nodesStore.nodes"
              :key="node.id"
              :value="node.id"
            >
              {{ node.name }} — {{ node.location }}
            </option>
          </select>
          <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
            Or click a location card in the sidebar
          </p>
        </div>

        <template v-if="selectedNodeObj">

          <!-- Location Info Header -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/30 p-4 sm:p-6">
            <div class="flex items-start gap-3">
              <CountryFlag :code="selectedNodeObj.country_code" size="lg" class="shrink-0" />
              <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ selectedNodeObj.name }}</h2>
                <p v-if="selectedNodeObj.provider" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ selectedNodeObj.provider }}</p>
                <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-400 dark:text-gray-500">
                  <span v-if="selectedNodeObj.city">{{ selectedNodeObj.city }}, {{ selectedNodeObj.country_code }}</span>
                  <span v-if="selectedNodeObj.asn" class="font-mono">{{ selectedNodeObj.asn }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Select Test Target -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/30 p-4 sm:p-6">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Select Test Target</h3>

            <!-- Custom IP input -->
            <div class="mb-5">
              <label :for="customTargetId" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Test Custom IP Address</label>
              <div class="flex gap-2">
                <input
                  :id="customTargetId"
                  v-model="customTargetInput"
                  type="text"
                  placeholder="Enter IP address (e.g., 8.8.8.8 or 2001:4860:4860::8888)"
                  class="flex-1 rounded-lg border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                  autocomplete="off"
                  spellcheck="false"
                  @keydown.enter.prevent="submitCustomTarget"
                />
                <button
                  class="shrink-0 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="!customTargetInput.trim()"
                  @click="submitCustomTarget"
                >Select</button>
              </div>
              <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                Enter your IP address to test connectivity from the selected server to your location
              </p>
            </div>

            <!-- Current target indicator -->
            <div v-if="testStore.target" class="mb-4 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm dark:border-primary-700 dark:bg-primary-900/20">
              <span class="text-gray-600 dark:text-gray-400">Custom Target — Selected Testing to:</span>
              <code class="ml-1 font-mono text-primary-700 dark:text-primary-300">{{ testStore.target }}</code>
            </div>

            <!-- Predefined target IPs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="t in testTargets"
                :key="t.ip"
                class="flex items-center justify-between rounded-lg border px-3 py-2.5 transition-all cursor-pointer"
                :class="
                  testStore.target === t.ip
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                    : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-gray-600'
                "
                @click="selectTarget(t.ip)"
              >
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <code class="font-mono text-sm text-primary-700 select-all dark:text-primary-300">{{ t.ip }}</code>
                    <span
                      class="rounded border px-1.5 py-0.5 text-[10px] font-bold uppercase"
                      :class="t.family === 'ipv4' ? 'border-green-300 bg-green-100 text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-400' : 'border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-400'"
                    >{{ t.family }}</span>
                  </div>
                  <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ t.label }} · Port: {{ t.port }}</div>
                </div>
                <svg v-if="testStore.target === t.ip" class="h-5 w-5 shrink-0 text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Download Test Files -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/30 p-4 sm:p-6">
            <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Download Test Files</h3>
            <div v-if="nodesStore.downloadsLoading" class="py-2">
              <LoadingSpinner size="sm" label="Loading files..." />
            </div>
            <div v-else-if="nodesStore.getDownloadsForNode(selectedNodeObj.id).length === 0" class="text-xs text-gray-600">
              No test files available for this location
            </div>
            <div v-else class="flex flex-wrap gap-2">
              <a
                v-for="file in nodesStore.getDownloadsForNode(selectedNodeObj.id)"
                :key="file.id"
                :href="file.url"
                target="_blank"
                rel="noopener"
                :class="downloadFileClasses(file, nodesStore.getDownloadsForNode(selectedNodeObj.id))"
              >
                <svg class="h-4 w-4 shrink-0" :class="downloadFileColor(file, nodesStore.getDownloadsForNode(selectedNodeObj.id)) === 'emerald' ? 'text-emerald-400' : downloadIconColors[downloadFileColor(file, nodesStore.getDownloadsForNode(selectedNodeObj.id))]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ file.size_label }}
              </a>
            </div>
          </div>

          <!-- Network Tests -->
          <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/30 p-4 sm:p-6">
            <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Network Tests</h3>

            <div v-if="testStore.error" role="alert" aria-live="polite" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
              {{ testStore.error }}
            </div>

            <div class="flex flex-wrap gap-3">
              <button
                v-for="type in (['ping', 'traceroute', 'mtr'] as TestType[])"
                :key="type"
                :disabled="testStore.submitting || testStore.isActive || !testStore.target.trim()"
                class="inline-flex items-center gap-2 rounded-lg border px-5 py-2.5 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                :class="testButtonClasses(type)"
                @click="showIperf3Panel = false; runTest(type)"
              >
                <svg v-if="testStore.submitting && testStore.selectedTestType === type" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ type === 'mtr' ? 'MTR Test' : type.charAt(0).toUpperCase() + type.slice(1) + ' Test' }}
              </button>

              <!-- iPerf3 button -->
              <button
                v-if="selectedNodeObj?.iperf3_enabled && selectedNodeObj?.hostname && selectedNodeObj?.iperf3_status === 'available'"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border px-5 py-2.5 text-sm font-semibold transition-colors"
                :class="testButtonClasses('iperf3')"
                @click="showIperf3Panel = !showIperf3Panel"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                iPerf3
              </button>

              <button
                v-if="testStore.hasResult || testStore.error"
                type="button"
                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white"
                @click="testStore.resetTest()"
              >New Test</button>
            </div>

            <!-- iPerf3 panel (shown inline when toggled) -->
            <div v-if="showIperf3Panel" class="mt-4">
              <Iperf3Test :node="selectedNodeObj ?? null" />
            </div>

            <p v-if="!testStore.target.trim() && !showIperf3Panel" class="mt-3 text-xs text-gray-400 dark:text-gray-500">
              Select a test target above before running a network test
            </p>
          </div>

        </template>

        <!-- Test Output -->
        <TestOutput />

        <!-- Test Results -->
        <TestResults />

        <!-- Your Connection -->
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/30 p-4 sm:p-6">
          <h3 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">Your Connection</h3>
          <p
            v-if="selectedNodeObj"
            class="mb-4 text-xs text-gray-400 dark:text-gray-500"
          >
            <span class="inline-flex items-center gap-1">
              as seen from <CountryFlag :code="selectedNodeObj.country_code" size="sm" /> {{ selectedNodeObj.name }}
            </span>
          </p>
          <p v-else class="mb-4 text-xs text-gray-400 dark:text-gray-500">
            as seen from the controller
          </p>

          <!-- Loading -->
          <div v-if="connectionLoading" class="py-4 text-center">
            <span class="text-sm text-gray-500">Detecting your connection...</span>
          </div>

          <!-- Error -->
          <div
            v-else-if="connectionError"
            role="alert"
            aria-live="polite"
            class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
          >
            {{ connectionError }}
          </div>

          <!-- Connection Info -->
          <div v-else-if="connectionInfo" class="space-y-3">
            <!-- IPv4 -->
            <div class="flex items-center justify-between gap-4">
              <span class="text-sm text-gray-500 dark:text-gray-400">Your IPv4</span>
              <div class="text-right">
                <template v-if="connectionInfo.ipv4.working">
                  <span class="rounded border border-green-300 bg-green-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-400">
                    working
                  </span>
                  <span class="ml-2 font-mono text-sm text-gray-900 dark:text-white">{{ connectionInfo.ipv4.address }}</span>
                </template>
                <template v-else>
                  <span class="rounded border border-gray-300 bg-gray-200 px-1.5 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500">
                    no IPv4
                  </span>
                  <span class="ml-2 text-sm text-gray-400 dark:text-gray-500">not available</span>
                </template>
              </div>
            </div>

            <!-- IPv6 -->
            <div class="flex items-center justify-between gap-4">
              <span class="text-sm text-gray-500 dark:text-gray-400">Your IPv6</span>
              <div class="text-right">
                <template v-if="connectionInfo.ipv6.working">
                  <span class="rounded border border-blue-300 bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-blue-800 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                    working
                  </span>
                  <span class="ml-2 font-mono text-sm text-gray-900 dark:text-white">{{ connectionInfo.ipv6.address }}</span>
                </template>
                <template v-else>
                  <span class="rounded border border-gray-300 bg-gray-200 px-1.5 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-500">
                    no IPv6
                  </span>
                  <span class="ml-2 text-sm text-gray-400 dark:text-gray-500">not available</span>
                </template>
              </div>
            </div>

            <!-- Reverse DNS -->
            <div
              v-if="connectionInfo.ipv4.reverse_dns || connectionInfo.ipv6.reverse_dns"
              class="flex items-center justify-between gap-4"
            >
              <span class="text-sm text-gray-500 dark:text-gray-400">Reverse DNS</span>
              <span class="font-mono text-sm text-gray-700 dark:text-gray-300">
                {{ connectionInfo.ipv4.reverse_dns || connectionInfo.ipv6.reverse_dns }}
              </span>
            </div>

            <!-- Network -->
            <div v-if="connectionInfo.network" class="flex items-center justify-between gap-4">
              <span class="text-sm text-gray-500 dark:text-gray-400">Your network</span>
              <div class="text-right">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ connectionInfo.network.isp || connectionInfo.network.org || connectionInfo.network.as_name }}
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500">
                  <span v-if="connectionInfo.network.asn">{{ connectionInfo.network.asn }}</span>
                  <span v-if="connectionInfo.network.country_code">
                    &middot; {{ connectionInfo.network.country_code }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Country / Location -->
            <div v-if="connectionInfo.network && (connectionInfo.network.country || connectionInfo.network.city)" class="flex items-center justify-between gap-4">
              <span class="text-sm text-gray-500 dark:text-gray-400">Location</span>
              <div class="text-right text-sm text-gray-900 dark:text-white">
                <span v-if="connectionInfo.network.country_code" class="mr-1">{{ getCountryFlag(connectionInfo.network.country_code) }}</span>
                <span v-if="connectionInfo.network.country">{{ connectionInfo.network.country }}</span>
                <span v-if="connectionInfo.network.region && connectionInfo.network.region !== connectionInfo.network.city"> &middot; {{ connectionInfo.network.region }}</span>
                <span v-if="connectionInfo.network.city"> &middot; {{ connectionInfo.network.city }}</span>
                <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">(approximate)</span>
              </div>
            </div>

            <!-- Timezone -->
            <div v-if="connectionInfo.network?.timezone" class="flex items-center justify-between gap-4">
              <span class="text-sm text-gray-500 dark:text-gray-400">Timezone</span>
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ connectionInfo.network.timezone }}</span>
            </div>

            <!-- Connection note -->
            <div
              v-if="connectionInfo.connection_note"
              class="rounded border border-blue-300/50 bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:border-blue-800/50 dark:bg-blue-900/10 dark:text-blue-400"
            >
              {{ connectionInfo.connection_note }}
            </div>

            <!-- Private IP notice -->
            <div
              v-if="connectionInfo.ipv4.address && !connectionInfo.ipv4.is_public && !connectionInfo.ipv6.is_public && !connectionInfo.connection_note"
              class="rounded border border-yellow-300/50 bg-yellow-50 px-3 py-2 text-xs text-yellow-700 dark:border-yellow-800/50 dark:bg-yellow-900/10 dark:text-yellow-500"
            >
              Detected a private/local IP — network details may be unavailable. This is expected when running locally.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
