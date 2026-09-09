<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { api } from '@/api/client'
import type { Iperf3Session, Node } from '@/types'

const props = defineProps<{ node: Node | null }>()

const session = ref<Iperf3Session | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)
const copied = ref<string | null>(null)
const remainingSeconds = ref(0)

let countdownTimer: ReturnType<typeof setInterval> | null = null

onUnmounted(() => { if (countdownTimer) clearInterval(countdownTimer) })

watch(() => props.node, () => {
  session.value = null; error.value = null; copied.value = null; remainingSeconds.value = 0
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
})

const statusText = computed(() => {
  const n = props.node
  if (!n) return null
  if (!n.iperf3_enabled) return { text: 'iPerf3 not enabled', color: 'text-gray-400' }
  if (n.maintenance) return { text: 'Node in maintenance', color: 'text-yellow-500' }
  if (n.iperf3_status !== 'available') return { text: `iPerf3: ${n.iperf3_status}`, color: 'text-gray-500' }
  if (!n.hostname) return { text: 'No hostname configured', color: 'text-gray-400' }
  return null
})

async function createSession() {
  if (!props.node || loading.value) return
  loading.value = true; error.value = null
  try {
    const res = await api.createIperf3Session(props.node.id)
    session.value = res.data
    startCountdown()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to create session'
  } finally { loading.value = false }
}

function startCountdown() {
  if (countdownTimer) clearInterval(countdownTimer)
  updateRemaining()
  countdownTimer = setInterval(() => {
    updateRemaining()
    if (remainingSeconds.value <= 0) {
      session.value = null
      if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
    }
  }, 1000)
}

function updateRemaining() {
  if (!session.value) { remainingSeconds.value = 0; return }
  remainingSeconds.value = Math.max(0, Math.round((new Date(session.value.expires_at).getTime() - Date.now()) / 1000))
}

async function copyCommand(text: string, label: string) {
  try { await navigator.clipboard.writeText(text) }
  catch {
    const ta = document.createElement('textarea'); ta.value = text
    ta.style.position = 'fixed'; ta.style.opacity = '0'
    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta)
  }
  copied.value = label; setTimeout(() => { copied.value = null }, 2000)
}

function fmtTime(s: number): string {
  return Math.floor(s / 60) > 0 ? `${Math.floor(s / 60)}m${(s % 60).toString().padStart(2, '0')}s` : `${s}s`
}
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/50">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">iPerf3 Test</h3>

    <p v-if="!node" class="text-xs text-gray-500 dark:text-gray-400">
      Select a testing location to view iPerf3 commands.
    </p>

    <template v-else-if="statusText">
      <p class="text-xs" :class="statusText.color">{{ statusText.text }}</p>
    </template>

    <template v-else-if="!session">
      <p class="text-xs text-gray-600 dark:text-gray-300 mb-3">
        Generate a short-lived iPerf3 session for <strong>{{ node!.hostname }}</strong>.
        Copy and run the command in your terminal.
      </p>
      <button
        class="rounded bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700 disabled:opacity-50 dark:bg-purple-500 dark:hover:bg-purple-600"
        :disabled="loading"
        @click="createSession"
      >{{ loading ? 'Creating session…' : 'Generate iPerf3 session' }}</button>
      <p v-if="error" class="mt-2 text-xs text-red-500">{{ error }}</p>
    </template>

    <template v-else-if="session && remainingSeconds > 0">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs text-gray-500 dark:text-gray-400">Expires in</span>
        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300">
          {{ fmtTime(remainingSeconds) }}
        </span>
      </div>
      <p class="text-[10px] text-gray-400 dark:text-gray-500 mb-3">
        iPerf3 must be installed on your computer. Copy a command below and run it in your terminal.
      </p>
      <div class="space-y-2">
        <div v-for="(cmd, key) in session.commands" :key="key">
          <template v-if="cmd">
            <span class="text-[10px] uppercase font-medium text-gray-400 dark:text-gray-500">
              {{ key === 'upload' ? 'TCP Upload' : key === 'download' ? 'TCP Download' : key === 'ipv6_upload' ? 'IPv6 Upload' : 'IPv6 Download' }}
            </span>
            <div class="flex items-start gap-2 mt-0.5">
              <pre class="flex-1 rounded bg-gray-100 dark:bg-gray-900 px-2 py-1.5 text-[11px] font-mono text-gray-800 dark:text-gray-200 overflow-x-auto whitespace-pre-wrap break-all">{{ cmd }}</pre>
              <button
                class="shrink-0 rounded border px-2 py-1 text-[10px] transition-colors"
                :class="copied === key
                  ? 'border-green-400 text-green-600 dark:text-green-400'
                  : 'border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-900 dark:border-gray-600 dark:text-gray-400 dark:hover:text-white'"
                @click="copyCommand(cmd as string, key as string)"
              >{{ copied === key ? '✓ Copied' : 'Copy' }}</button>
            </div>
          </template>
        </div>
      </div>
    </template>

    <template v-else-if="session && remainingSeconds <= 0">
      <p class="text-xs text-orange-500 mb-2">Session expired.</p>
      <button
        class="rounded bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700 disabled:opacity-50 dark:bg-purple-500 dark:hover:bg-purple-600"
        :disabled="loading"
        @click="createSession"
      >{{ loading ? 'Creating…' : 'Generate new session' }}</button>
    </template>
  </div>
</template>