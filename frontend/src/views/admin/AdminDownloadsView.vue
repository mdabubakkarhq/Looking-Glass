<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminDownloadFile } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const downloads = ref<AdminDownloadFile[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await adminApi.getDownloads(1, 100)
    downloads.value = res.data
  } catch { /* ignore */ } finally { loading.value = false }
})

async function deleteDownload(id: number) {
  if (!confirm('Delete this download file?')) return
  try { await adminApi.deleteDownload(id); downloads.value = downloads.value.filter(d => d.id !== id) }
  catch (err) { alert(err instanceof Error ? err.message : 'Failed') }
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}
</script>

<template>
  <div>
    <h2 class="mb-6 text-xl font-bold text-white">Download Files</h2>
    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading downloads..." /></div>
    <div v-else class="space-y-2">
      <div v-for="d in downloads" :key="d.id" class="flex items-center justify-between rounded-lg border border-gray-800 bg-gray-900 px-4 py-3">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-white">{{ d.name }}</span>
            <span class="rounded bg-gray-800 px-1.5 py-0.5 text-xs text-gray-400">{{ d.size_label }}</span>
            <span v-if="!d.enabled" class="rounded bg-red-900/50 px-1.5 py-0.5 text-xs text-red-400">disabled</span>
          </div>
          <div class="mt-1 text-xs text-gray-500">{{ formatBytes(d.size_bytes) }} {{ d.node ? '- ' + d.node.name : '' }}</div>
        </div>
        <button class="rounded border border-red-800 px-2 py-1 text-xs text-red-400 hover:bg-red-900/30" @click="deleteDownload(d.id)">Delete</button>
      </div>
      <div v-if="downloads.length === 0" class="py-12 text-center text-gray-500">No download files configured.</div>
    </div>
  </div>
</template>
