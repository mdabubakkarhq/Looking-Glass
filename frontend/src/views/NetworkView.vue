<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '@/api/client'
import type { NetworkInfo } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const network = ref<NetworkInfo | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const response = await api.getNetworkInfo()
    network.value = response.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load network information'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Network Information</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        Detailed information about our network, peering, and connectivity.
      </p>
    </div>

    <div v-if="loading" class="py-16" role="status" aria-live="polite">
      <LoadingSpinner size="lg" label="Loading network information..." />
    </div>

    <div v-else-if="error" role="alert" aria-live="polite" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
      {{ error }}
    </div>

    <div v-else-if="network" class="space-y-6">
      <!-- Network Overview -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/50">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Network Overview</h2>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div v-if="network.network_name">
            <dt class="text-sm text-gray-500">Network Name</dt>
            <dd class="mt-1 text-base font-medium text-gray-900 dark:text-white">{{ network.network_name }}</dd>
          </div>
          <div v-if="network.asn">
            <dt class="text-sm text-gray-500">ASN</dt>
            <dd class="mt-1 font-mono text-base font-medium text-gray-900 dark:text-white">AS{{ network.asn }}</dd>
          </div>
          <div v-if="network.peering_policy">
            <dt class="text-sm text-gray-500">Peering Policy</dt>
            <dd class="mt-1 text-base text-gray-900 dark:text-white">{{ network.peering_policy }}</dd>
          </div>
          <div v-if="network.peeringdb_url">
            <dt class="text-sm text-gray-500">PeeringDB</dt>
            <dd class="mt-1">
              <a
                :href="network.peeringdb_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 hover:underline"
              >
                {{ network.peeringdb_url }}
              </a>
            </dd>
          </div>
        </dl>
      </div>

      <!-- IPv4 Prefixes -->
      <div v-if="network.ipv4_prefixes.length" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/50">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">IPv4 Prefixes</h2>
        <div class="space-y-1">
          <div
            v-for="(prefix, i) in network.ipv4_prefixes"
            :key="i"
            class="rounded bg-gray-100 px-3 py-1.5 font-mono text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300"
          >
            {{ prefix }}
          </div>
        </div>
      </div>

      <!-- IPv6 Prefixes -->
      <div v-if="network.ipv6_prefixes.length" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/50">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">IPv6 Prefixes</h2>
        <div class="space-y-1">
          <div
            v-for="(prefix, i) in network.ipv6_prefixes"
            :key="i"
            class="rounded bg-gray-100 px-3 py-1.5 font-mono text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300"
          >
            {{ prefix }}
          </div>
        </div>
      </div>

      <!-- Internet Exchanges -->
      <div v-if="network.internet_exchanges.length" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/50">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Internet Exchanges</h2>
        <div class="space-y-3">
          <div
            v-for="(ix, i) in network.internet_exchanges"
            :key="i"
            class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800"
          >
            <span class="text-gray-900 dark:text-white">{{ ix.name }}</span>
            <a
              v-if="ix.url"
              :href="ix.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            >
              Website &rarr;
            </a>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/50">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Contact</h2>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div v-if="network.noc_contact">
            <dt class="text-sm text-gray-500">NOC Contact</dt>
            <dd class="mt-1 text-gray-900 dark:text-white">{{ network.noc_contact }}</dd>
          </div>
          <div v-if="network.abuse_contact">
            <dt class="text-sm text-gray-500">Abuse Contact</dt>
            <dd class="mt-1 text-gray-900 dark:text-white">{{ network.abuse_contact }}</dd>
          </div>
        </dl>
        <div
          v-if="!network.noc_contact && !network.abuse_contact"
          class="text-sm text-gray-500"
        >
          No contact information available.
        </div>
      </div>
    </div>
  </div>
</template>
