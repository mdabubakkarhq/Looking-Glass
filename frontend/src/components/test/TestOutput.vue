<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { Terminal } from '@xterm/xterm'
import { FitAddon } from '@xterm/addon-fit'
import { WebLinksAddon } from '@xterm/addon-web-links'
import '@xterm/xterm/css/xterm.css'
import { useTestStore } from '@/stores/test'

const testStore = useTestStore()

const terminalEl = ref<HTMLDivElement | null>(null)
let terminal: Terminal | null = null
let fitAddon: FitAddon | null = null
let resizeObserver: ResizeObserver | null = null

function initTerminal(): void {
  if (!terminalEl.value || terminal) return

  terminal = new Terminal({
    theme: {
      background: '#0f172a',
      foreground: '#e2e8f0',
      cursor: '#22d3ee',
      cursorAccent: '#0f172a',
      selectionBackground: '#1e3a5f',
      black: '#1e293b',
      red: '#f87171',
      green: '#4ade80',
      yellow: '#fbbf24',
      blue: '#60a5fa',
      magenta: '#c084fc',
      cyan: '#22d3ee',
      white: '#e2e8f0',
      brightBlack: '#94a3b8',
      brightRed: '#fca5a5',
      brightGreen: '#86efac',
      brightYellow: '#fcd34d',
      brightBlue: '#93c5fd',
      brightMagenta: '#d8b4fe',
      brightCyan: '#67e8f9',
      brightWhite: '#f8fafc',
    },
    fontFamily: '"JetBrains Mono", "Fira Code", "Cascadia Code", Menlo, monospace',
    fontSize: 13,
    lineHeight: 1.4,
    scrollback: 10000,
    cursorBlink: false,
    disableStdin: true,
    convertEol: true,
  })

  fitAddon = new FitAddon()
  terminal.loadAddon(fitAddon)
  terminal.loadAddon(new WebLinksAddon())

  terminal.open(terminalEl.value)
  fitAddon.fit()

  resizeObserver = new ResizeObserver(() => {
    fitAddon?.fit()
  })
  resizeObserver.observe(terminalEl.value)

  terminal.writeln('\x1b[38;2;148;163;184m Ready. Submit a test to see output.\x1b[0m')
}

onMounted(() => {
  void nextTick(() => initTerminal())
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  terminal?.dispose()
  terminal = null
  fitAddon = null
})

// Watch for new output lines and write them to the terminal
watch(
  () => testStore.outputLines.length,
  (newLen, oldLen) => {
    if (!terminal || newLen <= oldLen) return
    const lines = testStore.outputLines.slice(oldLen)
    for (const line of lines) {
      terminal.writeln(line.text)
    }
    terminal.scrollToBottom()
  },
)

// Clear terminal when test resets
watch(
  () => testStore.testStatus,
  (status) => {
    if (status === null && terminal) {
      terminal.clear()
      terminal.writeln('\x1b[38;2;148;163;184m Ready. Submit a test to see output.\x1b[0m')
    }
  },
)
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-gray-200 bg-[#0f172a] dark:border-gray-700">
    <!-- Terminal Header -->
    <div class="flex items-center gap-2 border-b border-slate-300 bg-gray-100 px-4 py-2 dark:border-gray-700 dark:bg-gray-800">
      <div class="flex gap-1.5">
        <span class="h-3 w-3 rounded-full bg-red-500/80"></span>
        <span class="h-3 w-3 rounded-full bg-yellow-500/80"></span>
        <span class="h-3 w-3 rounded-full bg-green-500/80"></span>
      </div>
      <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
        <span v-if="testStore.currentTest">
          {{ testStore.currentTest.test_type.toUpperCase() }}
          → {{ testStore.currentTest.target }}
          @ {{ testStore.currentTest.node_id }}
        </span>
        <span v-else>Terminal Output</span>
      </span>
      <span
        v-if="testStore.streaming"
        class="ml-auto inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400"
      >
        <span class="inline-block h-1.5 w-1.5 rounded-full bg-primary-600 animate-pulse dark:bg-primary-400"></span>
        Streaming
      </span>
    </div>

    <!-- Terminal Container -->
    <div ref="terminalEl" class="h-80 overflow-hidden"></div>
  </div>
</template>
