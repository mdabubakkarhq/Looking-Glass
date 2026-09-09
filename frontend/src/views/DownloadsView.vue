<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '@/api/client'
import type { DownloadFile } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const downloads = ref<DownloadFile[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const downloading = ref<Record<number, boolean>>({})
const downloadProgress = ref<Record<number, { loaded: number; total: number; speed: number }>>({})

onMounted(async () => {
  try {
    const response = await api.getDownloads()
    downloads.value = response.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load downloads'
  } finally {
    loading.value = false
  }
})

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]!}`
}

function formatSpeed(bytesPerSecond: number): string {
  if (bytesPerSecond === 0) return '—'
  return `${formatBytes(bytesPerSecond)}/s`
}

async function startDownload(file: DownloadFile): Promise<void> {
  if (downloading.value[file.id]) return

  downloading.value[file.id] = true
  downloadProgress.value[file.id] = { loaded: 0, total: file.size_bytes, speed: 0 }

  const startTime = Date.now()
  let lastLoaded = 0
  let lastTime = startTime

  try {
    const response = await fetch(file.url)

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const reader = response.body?.getReader()
    if (!reader) throw new Error('ReadableStream not supported')

    const contentLength = Number(response.headers.get('content-length')) || file.size_bytes

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      const now = Date.now()
      const elapsed = (now - lastTime) / 1000
      lastLoaded += value.length

      if (elapsed > 0.25) {
        const speed = (lastLoaded) / elapsed
        downloadProgress.value[file.id] = {
          loaded: lastLoaded,
          total: contentLength,
          speed,
        }
        lastLoaded = 0
        lastTime = now
      }
    }

    const totalElapsed = (Date.now() - startTime) / 1000
    downloadProgress.value[file.id] = {
      loaded: contentLength,
      total: contentLength,
      speed: totalElapsed > 0 ? contentLength / totalElapsed : 0,
    }
  } catch (err) {
    console.error('Download failed:', err)
  } finally {
    setTimeout(() => {
      downloading.value[file.id] = false
    }, 3000)
  }
}
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Download Speed Tests</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        Test your download speed by downloading test files directly from our nodes.
      </p>
    </div>

    <div v-if="loading" class="py-16" role="status" aria-live="polite">
      <LoadingSpinner size="lg" label="Loading available downloads..." />
    </div>

    <div v-else-if="error" role="alert" aria-live="polite" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
      {{ error }}
    </div>

    <div v-else-if="downloads.length === 0" class="py-16 text-center text-gray-400 dark:text-gray-500">
      No download files are currently available.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="file in downloads"
        :key="file.id"
        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/50 transition-colors hover:border-gray-300 dark:hover:border-gray-600"
      >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex items-center gap-2">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="font-medium text-gray-900 dark:text-white">{{ file.name }}</span>
              <span class="rounded bg-gray-200 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                {{ file.size_label }}
              </span>
            </div>
            <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
              {{ formatBytes(file.size_bytes) }} &middot; Node: {{ file.node_id }}
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div v-if="downloading[file.id] && downloadProgress[file.id]" class="text-right">
              <div class="text-sm font-medium text-primary-600 dark:text-primary-400">
                {{ formatSpeed(downloadProgress[file.id]!.speed) }}
              </div>
              <div class="mt-1 h-1.5 w-32 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                  class="h-full rounded-full bg-primary-500 transition-all duration-300"
                  :style="{
                    width: `${Math.min(100, (downloadProgress[file.id]!.loaded / downloadProgress[file.id]!.total) * 100)}%`,
                  }"
                />
              </div>
            </div>
            <button
              :disabled="downloading[file.id]"
              class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-500 disabled:opacity-50"
              @click="startDownload(file)"
            >
              <span v-if="downloading[file.id]" class="text-primary-200">Downloading...</span>
              <span v-else>Download</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
