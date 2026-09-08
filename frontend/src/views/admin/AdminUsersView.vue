<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminUser } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const users = ref<AdminUser[]>([])
const loading = ref(true)
const showAdd = ref(false)
const newUser = ref({ name: '', email: '', password: '', password_confirmation: '' })

onMounted(loadUsers)

async function loadUsers() {
  loading.value = true
  try {
    const res = await adminApi.getUsers(1, 100)
    users.value = res.data
  } catch { /* ignore */ } finally { loading.value = false }
}

async function addUser() {
  try {
    await adminApi.createUser(newUser.value)
    showAdd.value = false
    newUser.value = { name: '', email: '', password: '', password_confirmation: '' }
    await loadUsers()
  } catch (err) { alert(err instanceof Error ? err.message : 'Failed') }
}

async function deleteUser(user: AdminUser) {
  if (!confirm('Delete user "' + user.name + '"?')) return
  try { await adminApi.deleteUser(user.id); await loadUsers() }
  catch (err) { alert(err instanceof Error ? err.message : 'Failed') }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-bold text-white">Users</h2>
      <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500" @click="showAdd = !showAdd">
        {{ showAdd ? 'Cancel' : '+ Add User' }}
      </button>
    </div>

    <div v-if="showAdd" class="mb-6 rounded-lg border border-gray-800 bg-gray-900 p-5">
      <form @submit.prevent="addUser" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-gray-400">Name</label><input v-model="newUser.name" required class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" /></div>
        <div><label class="mb-1 block text-xs text-gray-400">Email</label><input v-model="newUser.email" type="email" required class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" /></div>
        <div><label class="mb-1 block text-xs text-gray-400">Password</label><input v-model="newUser.password" type="password" required minlength="8" class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" /></div>
        <div><label class="mb-1 block text-xs text-gray-400">Confirm Password</label><input v-model="newUser.password_confirmation" type="password" required class="w-full rounded border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white" /></div>
        <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Create User</button></div>
      </form>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading users..." /></div>
    <div v-else class="space-y-2">
      <div v-for="u in users" :key="u.id" class="flex items-center justify-between rounded-lg border border-gray-800 bg-gray-900 px-4 py-3">
        <div>
          <div class="text-sm font-medium text-white">{{ u.name }}</div>
          <div class="text-xs text-gray-500">{{ u.email }}  Joined {{ new Date(u.created_at).toLocaleDateString() }}</div>
        </div>
        <button class="rounded border border-red-800 px-2 py-1 text-xs text-red-400 hover:bg-red-900/30" @click="deleteUser(u)">Delete</button>
      </div>
      <div v-if="users.length === 0" class="py-12 text-center text-gray-500">No users found.</div>
    </div>
  </div>
</template>
