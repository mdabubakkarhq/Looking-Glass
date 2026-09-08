<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminSetting } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const settings = ref<AdminSetting[]>([])
const loading = ref(true)
const saving = ref(false)
const error = ref<string | null>(null)
const editValues = ref<Record<string, unknown>>({})
const successMsg = ref<string | null>(null)

onMounted(async () => {
  try {
    const res = await adminApi.getSettings()
    settings.value = res.data
    for (const s of res.data) {
      editValues.value[s.key] = s.value
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load settings'
  } finally {
    loading.value = false
  }
})

async function saveSettings() {
  saving.value = true
  successMsg.value = null
  try {
    const payload = settings.value.map(s => ({
      key: s.key,
      value: editValues.value[s.key] ?? null,
      type: s.type,
    }))
    await adminApi.updateSettings(payload)
    successMsg.value = 'Settings saved successfully.'
    setTimeout(() => { successMsg.value = null }, 3000)
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed to save settings')
  } finally {
    saving.value = false
  }
}

function groupLabel(g: string): string {
  return g.charAt(0).toUpperCase() + g.slice(1).replace(/_/g, ' ')
}

const grouped = ref<Record<string, AdminSetting[]>>({})
onMounted(() => {
  const g: Record<string, AdminSetting[]> = {}
  for (const s of settings.value) {
    if (!g[s.group]) g[s.group] = []
    g[s.group].push(s)
  }
  grouped.value = g
})
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-bold text-white">Settings</h2>
      <button
        :disabled="saving"
        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50"
        @click="saveSettings"
      >
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <div v-if="successMsg" class="mb-4 rounded-lg border border-green-800 bg-green-900/30 px-4 py-3 text-sm text-green-400">{{ successMsg }}</div>
    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading settings..." /></div>
    <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/20 px-4 py-3 text-red-400">{{ error }}</div>

    <div v-else class="space-y-6">
      <div v-for="s in settings" :key="s.id" class="rounded-lg border border-gray-800 bg-gray-900 p-4">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-white">{{ s.label || s.key }}</span>
              <span class="rounded bg-gray-800 px-1.5 py-0.5 text-xs text-gray-500">{{ s.group }}</span>
            </div>
            <div v-if="s.description" class="mt-1 text-xs text-gray-500">{{ s.description }}</div>
          </div>
          <div class="w-64">
            <input
              v-if="s.type === 'boolean'"
              type="checkbox"
              v-model="editValues[s.key]"
              class="h-5 w-5 rounded border-gray-600 bg-gray-800 text-primary-500"
            />
            <input
              v-else-if="s.type === 'integer'"
              type="number"
              v-model="editValues[s.key]"
              class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white"
            />
            <textarea
              v-else-if="s.type === 'text' || s.type === 'json'"
              v-model="editValues[s.key]"
              rows="3"
              class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white"
            />
            <input
              v-else
              type="text"
              v-model="editValues[s.key]"
              class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white"
            />
          </div>
        </div>
      </div>
      <div v-if="settings.length === 0" class="py-12 text-center text-gray-500">No settings found.</div>
    </div>
  </div>
</template>
