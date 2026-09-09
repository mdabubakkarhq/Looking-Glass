<script setup lang="ts">
import { useNodesStore } from '@/stores/nodes'
import { useComparisonStore } from '@/stores/comparison'
import { useAppStore } from '@/stores/app'

const nodesStore = useNodesStore()
const comp = useComparisonStore()
const appStore = useAppStore()

function isSelected(nodeId: string): boolean {
  return comp.selectedNodes.has(nodeId)
}

function onSubmit() {
  const selected = nodesStore.nodes.filter(n => comp.selectedNodes.has(n.id))
  void comp.runComparison(selected.map(n => ({ id: n.id, name: n.name })))
}

function selectAll() {
  comp.selectAllNodeIds(nodesStore.onlineNodes.map(n => n.id))
}
</script>

<template>
  <form @submit.prevent="onSubmit" class="space-y-5">
    <!-- Target Input -->
    <div>
      <label for="comp-target" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
        Target
      </label>
      <input
        id="comp-target"
        v-model="comp.target"
        type="text"
        placeholder="Enter hostname or IP address (e.g. 1.1.1.1)"
        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
        autocomplete="off"
        spellcheck="false"
      />
    </div>

    <!-- Test Type & IP Family -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label for="comp-type" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Test Type</label>
        <select
          id="comp-type"
          v-model="comp.testType"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option v-for="type in appStore.getTestTypes()" :key="type" :value="type">
            {{ type.toUpperCase() }}
          </option>
        </select>
      </div>
      <div>
        <label for="comp-family" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">IP Family</label>
        <select
          id="comp-family"
          v-model="comp.ipFamily"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        >
          <option v-for="family in appStore.getIpFamilies()" :key="family" :value="family">
            {{ family === 'auto' ? 'Auto' : family.toUpperCase() }}
          </option>
        </select>
      </div>
    </div>

    <!-- Node Selection -->
    <div>
      <div class="mb-2 flex items-center justify-between">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
          Select Nodes
          <span class="ml-1 text-xs text-gray-400 dark:text-gray-500">({{ comp.selectedNodes.size }}/{{ comp.maxNodes }})</span>
        </label>
        <div class="flex gap-2">
          <button
            type="button"
            class="text-xs text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            @click="selectAll"
          >
            Select All Online
          </button>
          <span class="text-gray-600">|</span>
          <button
            type="button"
            class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            @click="comp.clearSelection()"
          >
            Clear
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <label
          v-for="node in nodesStore.nodes"
          :key="node.id"
          class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-all"
          :class="[
            isSelected(node.id)
              ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
              : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-gray-600',
            node.status !== 'online' ? 'opacity-50' : '',
          ]"
        >
          <input
            type="checkbox"
            :checked="isSelected(node.id)"
            :disabled="node.status !== 'online' || (!isSelected(node.id) && comp.selectedNodes.size >= comp.maxNodes)"
            class="h-4 w-4 rounded border-gray-300 bg-white text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-primary-500"
            @change="comp.toggleNode(node.id)"
          />
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ node.name }}</span>
              <span
                class="inline-block h-2 w-2 rounded-full"
                :class="node.status === 'online' ? 'bg-green-500 dark:bg-green-400' : 'bg-gray-400 dark:bg-gray-500'"
              />
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ node.location }}</div>
          </div>
        </label>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="comp.globalError"
      class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400"
    >
      {{ comp.globalError }}
    </div>

    <!-- Submit -->
    <div class="flex items-center gap-3">
      <button
        type="submit"
        :disabled="comp.submitting || comp.isRunning || comp.selectedNodes.size < 2 || !comp.target.trim()"
        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <svg
          v-if="comp.submitting"
          class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span v-if="comp.isRunning">Running...</span>
        <span v-else>Run Comparison</span>
      </button>
      <span v-if="comp.selectedNodes.size < 2" class="text-xs text-gray-500">
        Select at least 2 nodes
      </span>
    </div>
  </form>
</template>
