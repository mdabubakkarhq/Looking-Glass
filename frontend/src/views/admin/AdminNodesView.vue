<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminNode } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const nodes = ref<AdminNode[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const showAdd = ref(false)
const newNode = ref({ name: '', city: '', country_code: '', ipv4: '', ipv6: '', provider: '', asn: '' })
const tokenResult = ref<string | null>(null)

onMounted(loadNodes)

async function loadNodes() {
  loading.value = true
  try {
    const res = await adminApi.getNodes(1, 100)
    nodes.value = res.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load nodes'
  } finally {
    loading.value = false
  }
}

async function addNode() {
  try {
    await adminApi.createNode(newNode.value)
    showAdd.value = false
    newNode.value = { name: '', city: '', country_code: '', ipv4: '', ipv6: '', provider: '', asn: '' }
    await loadNodes()
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed to create node')
  }
}

async function toggleMaintenance(node: AdminNode) {
  try {
    await adminApi.toggleNodeMaintenance(node.id)
    await loadNodes()
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed')
  }
}

async function generateToken(node: AdminNode) {
  try {
    const res = await adminApi.generateNodeToken(node.id)
    tokenResult.value = 'lg-agent register --controller=' + window.location.origin + ' --token=' + res.data.token
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed')
  }
}

async function deleteNode(node: AdminNode) {
  if (!confirm('Delete node "' + node.name + '"?')) return
  try {
    await adminApi.deleteNode(node.id)
    await loadNodes()
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed')
  }
}

function statusBadge(s: string): string {
  const map: Record<string, string> = {
    online: 'bg-green-900/50 text-green-400 border-green-800',
    offline: 'bg-red-900/50 text-red-400 border-red-800',
    maintenance: 'bg-yellow-900/50 text-yellow-400 border-yellow-800',
    error: 'bg-red-900/50 text-red-400 border-red-800',
  }
  return map[s] || 'bg-gray-800 text-gray-400 border-gray-700'
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-bold text-white">Node Management</h2>
      <button
        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500"
        @click="showAdd = !showAdd"
      >
        {{ showAdd ? 'Cancel' : '+ Add Node' }}
      </button>
    </div>

    <!-- Add Node Form -->
    <div v-if="showAdd" class="mb-6 rounded-lg border border-gray-800 bg-gray-900 p-5">
      <h3 class="mb-4 text-sm font-semibold text-white">New Node</h3>
      <form @submit.prevent="addNode" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label class="mb-1 block text-xs text-gray-400">Name *</label>
          <input v-model="newNode.name" required class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" placeholder="Dhaka DC" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-400">City</label>
          <input v-model="newNode.city" class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-400">Country Code</label>
          <input v-model="newNode.country_code" maxlength="2" class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" placeholder="BD" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-400">IPv4</label>
          <input v-model="newNode.ipv4" class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-400">Provider</label>
          <input v-model="newNode.provider" class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-400">ASN</label>
          <input v-model="newNode.asn" class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" />
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
          <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Create Node</button>
        </div>
      </form>
    </div>

    <!-- Token Result -->
    <div v-if="tokenResult" class="mb-4 rounded-lg border border-green-800 bg-green-900/30 p-4">
      <div class="mb-2 text-sm font-medium text-green-400">Registration Command:</div>
      <code class="block break-all rounded bg-gray-800 p-3 text-xs text-white">{{ tokenResult }}</code>
      <button class="mt-2 text-xs text-gray-400 hover:text-white" @click="tokenResult = null">Dismiss</button>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading nodes..." /></div>
    <div v-else-if="error" class="rounded-lg border border-red-800 bg-red-900/20 px-4 py-3 text-red-400">{{ error }}</div>

    <!-- Node List -->
    <div v-else class="space-y-3">
      <div v-for="node in nodes" :key="node.id" class="rounded-lg border border-gray-800 bg-gray-900 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex items-center gap-3">
              <span class="font-medium text-white">{{ node.name }}</span>
              <span class="rounded border px-2 py-0.5 text-xs font-medium" :class="statusBadge(node.status)">{{ node.status }}</span>
              <span v-if="node.maintenance" class="rounded border border-yellow-800 bg-yellow-900/50 px-2 py-0.5 text-xs text-yellow-400">maintenance</span>
            </div>
            <div class="mt-1 text-xs text-gray-500">
              {{ node.city }}{{ node.country_code ? ', ' + node.country_code : '' }}
              {{ node.ipv4 ? '  ' + node.ipv4 : '' }}
              {{ node.agent_version ? '  v' + node.agent_version : '' }}
              {{ node.last_seen_at ? '  Last seen: ' + new Date(node.last_seen_at).toLocaleString() : '' }}
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button class="rounded border border-gray-700 px-3 py-1.5 text-xs text-gray-300 hover:border-gray-600 hover:text-white" @click="generateToken(node)">Register Token</button>
            <button class="rounded border border-gray-700 px-3 py-1.5 text-xs text-gray-300 hover:border-gray-600 hover:text-white" @click="toggleMaintenance(node)">
              {{ node.maintenance ? 'Disable Maint.' : 'Maintenance' }}
            </button>
            <button class="rounded border border-red-800 px-3 py-1.5 text-xs text-red-400 hover:bg-red-900/30" @click="deleteNode(node)">Delete</button>
          </div>
        </div>
      </div>
      <div v-if="nodes.length === 0" class="py-12 text-center text-gray-500">No nodes configured. Add your first node above.</div>
    </div>
  </div>
</template>
