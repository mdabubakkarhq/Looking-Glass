<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminNode } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import CountryFlag from '@/components/common/CountryFlag.vue'

const nodes = ref<AdminNode[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const showAdd = ref(false)
const editingNode = ref<AdminNode | null>(null)
const newNode = reactive({
  name: '', hostname: '', city: '', country_code: '',
  ipv4: '', ipv6: '', ipv4_enabled: true, ipv6_enabled: false,
  provider: '', asn: '', latency_enabled: true,
  iperf3_enabled: false, iperf3_port: null as number | null,
})
const editForm = reactive({
  name: '', hostname: '', city: '', country_code: '',
  ipv4: '', ipv6: '', ipv4_enabled: true, ipv6_enabled: false,
  provider: '', asn: '', latency_enabled: true,
  iperf3_enabled: false, iperf3_port: null as number | null,
})
const tokenResult = ref<string | null>(null)
const dnsResolving = ref(false)

async function resolveDns(target: 'new' | 'edit') {
  const hostname = target === 'new' ? newNode.hostname : editForm.hostname
  if (!hostname) return
  dnsResolving.value = true
  try {
    const res = await adminApi.resolveDns(hostname)
    const { ipv4, ipv6, has_ipv4, has_ipv6 } = res.data
    if (target === 'new') {
      if (has_ipv4 && ipv4[0] && !newNode.ipv4) newNode.ipv4 = ipv4[0]
      if (has_ipv6 && ipv6[0] && !newNode.ipv6) newNode.ipv6 = ipv6[0]
      if (has_ipv4) newNode.ipv4_enabled = true
      if (has_ipv6) newNode.ipv6_enabled = true
    } else {
      if (has_ipv4 && ipv4[0] && !editForm.ipv4) editForm.ipv4 = ipv4[0]
      if (has_ipv6 && ipv6[0] && !editForm.ipv6) editForm.ipv6 = ipv6[0]
      if (has_ipv4) editForm.ipv4_enabled = true
      if (has_ipv6) editForm.ipv6_enabled = true
    }
  } catch (err) {
    alert(err instanceof Error ? err.message : 'DNS resolution failed')
  } finally {
    dnsResolving.value = false
  }
}

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
    await adminApi.createNode({ ...newNode })
    showAdd.value = false
    resetNewNode()
    await loadNodes()
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed to create node')
  }
}

function resetNewNode() {
  Object.assign(newNode, {
    name: '', hostname: '', city: '', country_code: '',
    ipv4: '', ipv6: '', ipv4_enabled: true, ipv6_enabled: false,
    provider: '', asn: '', latency_enabled: true,
    iperf3_enabled: false, iperf3_port: null,
  })
}

function startEdit(node: AdminNode) {
  editingNode.value = node
  Object.assign(editForm, {
    name: node.name, hostname: node.hostname || '', city: node.city || '',
    country_code: node.country_code || '', ipv4: node.ipv4 || '', ipv6: node.ipv6 || '',
    ipv4_enabled: node.ipv4_enabled, ipv6_enabled: node.ipv6_enabled,
    provider: node.provider || '', asn: node.asn || '',
    latency_enabled: node.latency_enabled, iperf3_enabled: node.iperf3_enabled,
    iperf3_port: node.iperf3_port,
  })
}

function cancelEdit() { editingNode.value = null }

async function saveEdit() {
  if (!editingNode.value) return
  try {
    await adminApi.updateNode(editingNode.value.id, { ...editForm })
    editingNode.value = null
    await loadNodes()
  } catch (err) {
    alert(err instanceof Error ? err.message : 'Failed to update node')
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
    online: 'bg-green-100 text-green-700 border-green-300 dark:bg-green-900/50 dark:text-green-400 dark:border-green-800',
    offline: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900/50 dark:text-red-400 dark:border-red-800',
    maintenance: 'bg-yellow-100 text-yellow-700 border-yellow-300 dark:bg-yellow-900/50 dark:text-yellow-400 dark:border-yellow-800',
    error: 'bg-red-100 text-red-700 border-red-300 dark:bg-red-900/50 dark:text-red-400 dark:border-red-800',
  }
  return map[s] || 'bg-gray-100 text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'
}
</script>
<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">Node Management</h2>
      <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500" @click="showAdd = !showAdd; editingNode = null">
        {{ showAdd ? 'Cancel' : '+ Add Node' }}
      </button>
    </div>

    <!-- Add Node Form -->
    <div v-if="showAdd" class="mb-6 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">New Node</h3>
      <form @submit.prevent="addNode" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Name *</label>
          <input v-model="newNode.name" placeholder="NYC-01" required class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Hostname / FQDN</label>
          <div class="flex gap-2">
            <input v-model="newNode.hostname" placeholder="nyc01.example.com" class="flex-1 rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            <button type="button" class="shrink-0 rounded border border-gray-300 px-2 py-2 text-[10px] text-gray-600 hover:text-gray-900 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white disabled:opacity-50" :disabled="!newNode.hostname || dnsResolving" @click="resolveDns('new')">{{ dnsResolving ? '…' : 'Resolve' }}</button>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">City</label>
          <input v-model="newNode.city" placeholder="New York" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Country Code</label>
          <input v-model="newNode.country_code" placeholder="US" maxlength="2" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">IPv4 Address</label>
          <input v-model="newNode.ipv4" placeholder="192.0.2.1" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">IPv6 Address</label>
          <input v-model="newNode.ipv6" placeholder="2001:db8::1" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div class="flex items-end gap-6">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="newNode.ipv4_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            IPv4 enabled
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="newNode.ipv6_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            IPv6 enabled
          </label>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Provider</label>
          <input v-model="newNode.provider" placeholder="Vultr" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">ASN</label>
          <input v-model="newNode.asn" placeholder="AS64500" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div class="flex items-end gap-6">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="newNode.latency_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            Latency
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="newNode.iperf3_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            iPerf3
          </label>
        </div>
        <div v-if="newNode.iperf3_enabled">
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">iPerf3 Port</label>
          <input v-model.number="newNode.iperf3_port" type="number" min="1" max="65535" placeholder="5201" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
          <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Create Node</button>
        </div>
      </form>
    </div>

    <!-- Edit Node Form -->
    <div v-if="editingNode" class="mb-6 rounded-lg border border-blue-300 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-900/20">
      <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Edit: {{ editingNode.name }}</h3>
      <form @submit.prevent="saveEdit" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Name</label>
          <input v-model="editForm.name" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Hostname / FQDN</label>
          <div class="flex gap-2">
            <input v-model="editForm.hostname" placeholder="nyc01.example.com" class="flex-1 rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            <button type="button" class="shrink-0 rounded border border-gray-300 px-2 py-2 text-[10px] text-gray-600 hover:text-gray-900 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white disabled:opacity-50" :disabled="!editForm.hostname || dnsResolving" @click="resolveDns('edit')">{{ dnsResolving ? '…' : 'Resolve' }}</button>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">City</label>
          <input v-model="editForm.city" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Country Code</label>
          <input v-model="editForm.country_code" maxlength="2" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">IPv4 Address</label>
          <input v-model="editForm.ipv4" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">IPv6 Address</label>
          <input v-model="editForm.ipv6" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div class="flex items-end gap-6">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="editForm.ipv4_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            IPv4 enabled
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="editForm.ipv6_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            IPv6 enabled
          </label>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Provider</label>
          <input v-model="editForm.provider" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">ASN</label>
          <input v-model="editForm.asn" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div class="flex items-end gap-6">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="editForm.latency_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            Latency
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="editForm.iperf3_enabled" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
            iPerf3
          </label>
        </div>
        <div v-if="editForm.iperf3_enabled">
          <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">iPerf3 Port</label>
          <input v-model.number="editForm.iperf3_port" type="number" min="1" max="65535" placeholder="5201" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
        </div>
        <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-3">
          <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Save Changes</button>
          <button type="button" @click="cancelEdit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</button>
        </div>
      </form>
    </div>

    <!-- Token Result -->
    <div v-if="tokenResult" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/30">
      <div class="mb-2 text-sm font-medium text-green-700 dark:text-green-400">Registration Command:</div>
      <code class="block break-all rounded bg-gray-100 p-3 text-xs text-gray-900 dark:bg-gray-800 dark:text-white">{{ tokenResult }}</code>
      <button class="mt-2 text-xs text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" @click="tokenResult = null">Dismiss</button>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading nodes..." /></div>
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">{{ error }}</div>

    <!-- Node List -->
    <div v-else class="space-y-3">
      <div v-for="node in nodes" :key="node.id" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-medium text-gray-900 dark:text-white">{{ node.name }}</span>
              <span class="rounded border px-2 py-0.5 text-xs font-medium" :class="statusBadge(node.status)">{{ node.status }}</span>
              <span v-if="node.maintenance" class="rounded border border-yellow-300 bg-yellow-100 px-2 py-0.5 text-xs text-yellow-700 dark:border-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400">maintenance</span>
            </div>
            <div v-if="node.hostname" class="mt-1 text-xs text-gray-600 dark:text-gray-400">
              <span class="font-mono">{{ node.hostname }}</span>
            </div>
            <div class="mt-1 text-xs text-gray-500">
              <CountryFlag v-if="node.country_code" :code="node.country_code" size="sm" class="mr-1 inline-block align-middle" />{{ node.city }}{{ node.country_code ? ', ' + node.country_code : '' }}
              <span v-if="node.provider" class="text-gray-400 dark:text-gray-500">&middot; {{ node.provider }}</span>
              {{ node.agent_version ? '  v' + node.agent_version : '' }}
              {{ node.last_seen_at ? '  Last seen: ' + new Date(node.last_seen_at).toLocaleString() : '' }}
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
              <span v-if="node.ipv4" class="rounded border px-1.5 py-0.5 font-bold" :class="node.ipv4_enabled ? 'border-green-300 bg-green-100 text-green-800 dark:border-green-700 dark:bg-green-900/40 dark:text-green-400' : 'border-gray-300 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'">IPv4</span>
              <span v-if="node.ipv6" class="rounded border px-1.5 py-0.5 font-bold" :class="node.ipv6_enabled ? 'border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-400' : 'border-gray-300 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'">IPv6</span>
              <span v-if="node.latency_enabled" class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Latency</span>
              <span v-if="node.iperf3_enabled" class="rounded border px-1.5 py-0.5 font-medium" :class="node.iperf3_status === 'available' ? 'border-green-300 bg-green-100 text-green-700 dark:border-green-800 dark:bg-green-900/50 dark:text-green-400' : 'border-gray-300 bg-gray-100 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'">iPerf3: {{ node.iperf3_status }}{{ node.iperf3_port ? ':' + node.iperf3_port : '' }}</span>
              <span v-if="!node.ipv4 && !node.ipv6" class="text-gray-400 dark:text-gray-600">No IPs configured</span>
            </div>
          </div>
            <!-- Health check timestamps -->
            <div class="mt-1 flex flex-wrap gap-x-3 text-[9px] text-gray-400 dark:text-gray-500">
              <span v-if="node.ipv4 && node.ipv4_enabled && node.last_ipv4_health_check_at" :title="'IPv4 last checked: ' + new Date(node.last_ipv4_health_check_at).toLocaleString()">
                IPv4 ✓ {{ new Date(node.last_ipv4_health_check_at).toLocaleDateString() }}
              </span>
              <span v-if="node.ipv6 && node.ipv6_enabled && node.last_ipv6_health_check_at" :title="'IPv6 last checked: ' + new Date(node.last_ipv6_health_check_at).toLocaleString()">
                IPv6 ✓ {{ new Date(node.last_ipv6_health_check_at).toLocaleDateString() }}
              </span>
              <span v-if="node.iperf3_enabled && node.last_iperf3_health_check_at" :title="'iPerf3 last checked: ' + new Date(node.last_iperf3_health_check_at).toLocaleString()">
                iPerf3 ✓ {{ new Date(node.last_iperf3_health_check_at).toLocaleDateString() }}
              </span>
            </div>

          <div class="flex shrink-0 items-center gap-2">
            <button class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white" @click="startEdit(node)">Edit</button>
            <button class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white" @click="generateToken(node)">Register Token</button>
            <button class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:border-gray-400 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white" @click="toggleMaintenance(node)">
              {{ node.maintenance ? 'Disable Maint.' : 'Maintenance' }}
            </button>
            <button class="rounded border border-red-300 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30" @click="deleteNode(node)">Delete</button>
          </div>
        </div>
      </div>
      <div v-if="nodes.length === 0" class="py-12 text-center text-gray-500">No nodes configured. Add your first node above.</div>
    </div>
  </div>
</template>
