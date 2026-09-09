<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { useNodesStore } from '@/stores/nodes'

const appStore = useAppStore()
const nodesStore = useNodesStore()

const contactEmail = computed(() => {
  return appStore.config?.email || appStore.config?.abuse_contact || ''
})
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">Peering Information</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        Network and peering details for {{ appStore.getOrganization() || appStore.getSiteName() }}.
      </p>
    </div>

    <div class="space-y-6">
      <!-- Peering Policy -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Peering Policy</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          We maintain an open peering policy. If you are interested in peering with us,
          please reach out using the contact information below.
        </p>
      </div>

      <!-- Network Details -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Network Details</h2>
        <div v-if="nodesStore.onlineNodes.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div v-for="node in nodesStore.onlineNodes" :key="node.id" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ node.name }}</div>
            <div class="mt-1 text-xs text-gray-400 dark:text-gray-400">{{ node.location }}</div>
            <div class="mt-3 space-y-1.5 text-xs">
              <div v-if="node.asn" class="flex justify-between">
                <span class="text-gray-500">ASN</span>
                <span class="font-mono text-gray-700 dark:text-gray-300">{{ node.asn }}</span>
              </div>
              <div v-if="node.ipv4" class="flex justify-between">
                <span class="text-gray-500">IPv4</span>
                <span class="font-mono text-gray-700 dark:text-gray-300">{{ node.ipv4 }}</span>
              </div>
              <div v-if="node.ipv6" class="flex justify-between">
                <span class="text-gray-500">IPv6</span>
                <span class="font-mono text-gray-700 dark:text-gray-300">{{ node.ipv6 }}</span>
              </div>
              <div v-if="node.provider" class="flex justify-between">
                <span class="text-gray-500">Provider</span>
                <span class="text-gray-700 dark:text-gray-300">{{ node.provider }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="nodesStore.loading" class="text-sm text-gray-500 dark:text-gray-400">
          Loading network details...
        </div>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400">
          No online nodes available to display network details.
        </p>
      </div>

      <!-- Contact -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Contact</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          For peering requests, network inquiries, or abuse reports, please contact:
        </p>
        <div v-if="contactEmail" class="mt-3 text-sm">
          <a :href="`mailto:${contactEmail}`" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
            {{ contactEmail }}
          </a>
        </div>
        <p v-else class="mt-2 text-sm text-gray-400 dark:text-gray-500">
          Contact information has not been configured.
        </p>
      </div>
    </div>
  </div>
</template>
