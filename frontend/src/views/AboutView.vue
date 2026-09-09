<script setup lang="ts">
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">About</h1>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        Learn more about {{ appStore.getOrganization() || 'this' }} network diagnostic service.
      </p>
    </div>

    <div class="space-y-6">
      <!-- What is this -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">What is a Looking Glass?</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          A Looking Glass is a web-based interface that allows users to run network
          diagnostic tools such as Ping, Traceroute, MTR, and DNS queries against
          our infrastructure. It provides transparency into our network connectivity
          and allows you to test the reachability and latency to your services from
          our various global points of presence.
        </p>
      </div>

      <!-- Available Tests -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Available Tests</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-primary-600 dark:text-primary-400">Ping</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">
              Measures round-trip time and packet loss to a target host using ICMP echo requests.
            </p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-primary-600 dark:text-primary-400">Traceroute</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">
              Displays the network path (hops) packets take to reach the destination.
            </p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-primary-600 dark:text-primary-400">MTR</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">
              Combines ping and traceroute to provide a continuously updated view of the route and latency.
            </p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-sm font-semibold text-primary-600 dark:text-primary-400">DNS</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-400">
              Queries DNS records for a domain name to verify resolution from our network.
            </p>
          </div>
        </div>
      </div>

      <!-- Multi-Location -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Multi-Location Comparison</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          Run the same test from multiple locations simultaneously to compare latency,
          packet loss, and routing paths across our global network. Visit the
          <router-link to="/compare" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">Compare</router-link>
          page to get started.
        </p>
      </div>

      <!-- Powered By -->
      <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800/30">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Powered By</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          This service is powered by
          <a href="https://github.com/openlookingglass/open-looking-glass" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
            Open Looking Glass
          </a>,
          an open-source network monitoring and diagnostic platform.
        </p>
      </div>
    </div>
  </div>
</template>
