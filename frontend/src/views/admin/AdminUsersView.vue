<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminUser } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const users = ref<AdminUser[]>([])
const loading = ref(true)
const showForm = ref(false)
const editingUser = ref<AdminUser | null>(null)
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const formSaving = ref(false)

onMounted(loadUsers)

async function loadUsers() {
  loading.value = true
  try {
    const res = await adminApi.getUsers(1, 100)
    users.value = res.data
  } catch { /* ignore */ } finally { loading.value = false }
}

function openForm(user?: AdminUser) {
  if (user) {
    editingUser.value = user
    form.name = user.name
    form.email = user.email
    form.password = ''
    form.password_confirmation = ''
  } else {
    editingUser.value = null
    form.name = ''
    form.email = ''
    form.password = ''
    form.password_confirmation = ''
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editingUser.value = null
}

async function saveUser() {
  formSaving.value = true
  try {
    if (editingUser.value) {
      const data: Record<string, string> = { name: form.name, email: form.email }
      if (form.password) {
        data.password = form.password
        data.password_confirmation = form.password_confirmation
      }
      await adminApi.updateUser(editingUser.value.id, data as any)
    } else {
      await adminApi.createUser({ name: form.name, email: form.email, password: form.password, password_confirmation: form.password_confirmation })
    }
    closeForm()
    await loadUsers()
  } catch (err) { alert(err instanceof Error ? err.message : 'Failed') }
  finally { formSaving.value = false }
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
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">Users</h2>
      <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500" @click="showForm ? closeForm() : openForm()">
        {{ showForm ? 'Cancel' : '+ Add User' }}
      </button>
    </div>

    <div v-if="showForm" class="mb-6 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
      <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">{{ editingUser ? 'Edit User' : 'Add User' }}</h3>
      <form @submit.prevent="saveUser" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs text-gray-400">Name</label><input v-model="form.name" required class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" /></div>
        <div><label class="mb-1 block text-xs text-gray-400">Email</label><input v-model="form.email" type="email" required class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" /></div>
        <div><label class="mb-1 block text-xs text-gray-400">Password {{ editingUser ? '(leave blank to keep current)' : '' }}</label><input v-model="form.password" type="password" :required="!editingUser" minlength="8" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" /></div>
        <div><label class="mb-1 block text-xs text-gray-400">Confirm Password</label><input v-model="form.password_confirmation" type="password" :required="!editingUser && !!form.password" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" /></div>
        <div class="sm:col-span-2 flex gap-2">
          <button type="submit" :disabled="formSaving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50">
            {{ formSaving ? 'Saving...' : (editingUser ? 'Update User' : 'Create User') }}
          </button>
          <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" @click="closeForm">Cancel</button>
        </div>
      </form>
    </div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading users..." /></div>
    <div v-else class="space-y-2">
      <div v-for="u in users" :key="u.id" class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
        <div>
          <div class="text-sm font-medium text-gray-900 dark:text-white">{{ u.name }}</div>
          <div class="text-xs text-gray-500">{{ u.email }}  Joined {{ new Date(u.created_at).toLocaleDateString() }}</div>
        </div>
        <div class="flex gap-2">
          <button class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800" @click="openForm(u)">Edit</button>
          <button class="rounded border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30" @click="deleteUser(u)">Delete</button>
        </div>
      </div>
      <div v-if="users.length === 0" class="py-12 text-center text-gray-500">No users found.</div>
    </div>
  </div>
</template>
