<script setup lang="ts">
import { useTestStore } from '@/stores/test'
import { useNodesStore } from '@/stores/nodes'
import { useAppStore } from '@/stores/app'

const testStore = useTestStore()
const nodesStore = useNodesStore()
const appStore = useAppStore()

function onSubmit() {
  void testStore.submitTest()
}
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-4">
    <!-- Node Selector -->
    <div>
      <label for="node-select" class="mb-1.5 block text-sm font-medium text-gray-300">
        Node
      </label>
      <select
        id="node-select"
        v-model="testStore.selectedNode"
        class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
      >
        <option value="" disabled>Select a node...</option>
        <option
          v-for="node in nodesStore.onlineNodes"
          :key="node.id"
          :value="node.id"
        >
          {{ node.name }} — {{ node.location }}
        </option>
      </select>
    </div>

    <!-- Test Type & IP Family Row -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label for="test-type" class="mb-1.5 block text-sm font-medium text-gray-300">
          Test Type
        </label>
        <select
          id="test-type"
          v-model="testStore.selectedTestType"
          class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        >
          <option
            v-for="type in appStore.getTestTypes()"
            :key="type"
            :value="type"
          >
            {{ type.toUpperCase() }}
          </option>
        </select>
      </div>

      <div>
        <label for="ip-family" class="mb-1.5 block text-sm font-medium text-gray-300">
          IP Family
        </label>
        <select
          id="ip-family"
          v-model="testStore.selectedIpFamily"
          class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        >
          <option
            v-for="family in appStore.getIpFamilies()"
            :key="family"
            :value="family"
          >
            {{ family === 'auto' ? 'Auto' : family.toUpperCase() }}
          </option>
        </select>
      </div>
    </div>

    <!-- Target Input -->
    <div>
      <label for="target-input" class="mb-1.5 block text-sm font-medium text-gray-300">
        Target
      </label>
      <input
        id="target-input"
        v-model="testStore.target"
        type="text"
        placeholder="Enter hostname or IP address (e.g. 1.1.1.1 or google.com)"
        class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 text-sm text-white placeholder-gray-500 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
        autocomplete="off"
        spellcheck="false"
      />
    </div>

    <!-- Error Message -->
    <div
      v-if="testStore.error"
      class="rounded-lg border border-red-800 bg-red-900/30 px-4 py-3 text-sm text-red-400"
    >
      {{ testStore.error }}
    </div>

    <!-- Submit Button -->
    <div class="flex items-center gap-3">
      <button
        type="submit"
        :disabled="testStore.submitting || testStore.isActive || !testStore.selectedNode || !testStore.target.trim()"
        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <svg
          v-if="testStore.submitting"
          class="h-4 w-4 animate-spin"
          viewBox="0 0 24 24"
          fill="none"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span v-if="testStore.submitting">Submitting...</span>
        <span v-else-if="testStore.isActive">Running...</span>
        <span v-else>Run Test</span>
      </button>

      <button
        v-if="testStore.hasResult || testStore.error"
        type="button"
        class="rounded-lg border border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-300 transition-colors hover:border-gray-600 hover:text-white"
        @click="testStore.resetTest()"
      >
        New Test
      </button>
    </div>
  </form>
</template>
