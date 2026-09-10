<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { adminApi } from '@/api/admin'
import type { AdminSetting, AdminMenuItem, AdminFooterSection, AdminFooterLink } from '@/types'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

type Tab = 'general' | 'header' | 'footer' | 'smtp' | 'security'
const activeTab = ref<Tab>('general')
const tabs: { key: Tab; label: string }[] = [
  { key: 'general', label: 'General' },
  { key: 'header', label: 'Header' },
  { key: 'footer', label: 'Footer' },
  { key: 'smtp', label: 'SMTP' },
  { key: 'security', label: 'Security' },
]

const loading = ref(true)
const saving = ref(false)
const error = ref<string | null>(null)
const successMsg = ref<string | null>(null)

function showSuccess(msg: string) {
  successMsg.value = msg
  setTimeout(() => { successMsg.value = null }, 3000)
}
function showError(msg: string) {
  error.value = msg
  setTimeout(() => { error.value = null }, 5000)
}

// ── General ─────────────────────────────────────────────────
const settings = ref<AdminSetting[]>([])
const editValues = ref<Record<string, string | number | boolean | null>>({})
const uploadingField = ref<string | null>(null)
// Keys managed by the Footer tab — exclude from Branding section
const footerSettingKeys = ['footer_description', 'copyright_text']
const generalSettings = computed(() => settings.value.filter(s => s.group === 'general'))
const brandingSettings = computed(() => settings.value.filter(s => s.group === 'branding' && !footerSettingKeys.includes(s.key)))

async function loadSettings() {
  const res = await adminApi.getSettings()
  settings.value = res.data
  // Build a new object and replace the entire ref value so Vue picks up every key
  const values: Record<string, string | number | boolean | null> = {}
  for (const s of res.data) {
    values[s.key] = (s.value as string | number | boolean | null) ?? null
  }
  editValues.value = values
}

async function uploadImage(key: string, event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  uploadingField.value = key
  try {
    const res = await adminApi.uploadMedia(file, 'media')
    editValues.value[key] = res.data.url
    showSuccess('Image uploaded.')
  } catch (err) {
    showError(err instanceof Error ? err.message : 'Upload failed')
  } finally {
    uploadingField.value = null
    input.value = ''
  }
}

function getSettingLabel(key: string): string {
  return settings.value.find(s => s.key === key)?.label ?? key.replace(/_/g, ' ')
}
function getSettingDesc(key: string): string {
  return settings.value.find(s => s.key === key)?.description ?? ''
}

const imageFields = ['site_logo', 'og_image', 'favicon']
function isImageField(key: string): boolean {
  return imageFields.includes(key)
}
function onImageError(event: Event) {
  ;(event.target as HTMLImageElement).style.display = 'none'
}

// ── Header (Menu Items) ─────────────────────────────────────
const menuItems = ref<AdminMenuItem[]>([])
const menuFormOpen = ref(false)
const editingMenu = ref<AdminMenuItem | null>(null)
const menuForm = reactive({ label: '', url: '/', sort_order: 0, open_new_tab: false, active: true })
const menuSaving = ref(false)

async function loadMenuItems() {
  const res = await adminApi.getMenuItems()
  menuItems.value = res.data
}

function openMenuForm(item?: AdminMenuItem) {
  if (item) {
    editingMenu.value = item
    menuForm.label = item.label
    menuForm.url = item.url
    menuForm.sort_order = item.sort_order
    menuForm.open_new_tab = item.open_new_tab
    menuForm.active = item.active
  } else {
    editingMenu.value = null
    menuForm.label = ''
    menuForm.url = '/'
    menuForm.sort_order = menuItems.value.length
    menuForm.open_new_tab = false
    menuForm.active = true
  }
  menuFormOpen.value = true
}

function closeMenuForm() { menuFormOpen.value = false; editingMenu.value = null }

async function saveMenuItem() {
  menuSaving.value = true
  try {
    if (editingMenu.value) {
      await adminApi.updateMenuItem(editingMenu.value.id, { ...menuForm })
      showSuccess('Menu item updated.')
    } else {
      await adminApi.createMenuItem({ ...menuForm })
      showSuccess('Menu item created.')
    }
    closeMenuForm()
    await loadMenuItems()
  } catch (err) {
    showError(err instanceof Error ? err.message : 'Failed to save')
  } finally { menuSaving.value = false }
}

async function deleteMenuItem(id: number) {
  if (!confirm('Delete this menu item?')) return
  try {
    await adminApi.deleteMenuItem(id)
    showSuccess('Menu item deleted.')
    await loadMenuItems()
  } catch (err) { showError(err instanceof Error ? err.message : 'Failed to delete') }
}
// ── Footer ──────────────────────────────────────────────────
const footerSections = ref<AdminFooterSection[]>([])
const expandedSection = ref<number | null>(null)
const sectionFormOpen = ref(false)
const editingSection = ref<AdminFooterSection | null>(null)
const sectionForm = reactive({ title: '', sort_order: 0 })
const sectionSaving = ref(false)

const linkFormOpen = ref(false)
const editingLink = ref<AdminFooterLink | null>(null)
const linkForm = reactive({ footer_section_id: 0, label: '', url: '', external: false, sort_order: 0 })
const linkSaving = ref(false)

async function loadFooter() {
  const res = await adminApi.getFooter()
  footerSections.value = res.data
}

function openSectionForm(section?: AdminFooterSection) {
  if (section) {
    editingSection.value = section
    sectionForm.title = section.title
    sectionForm.sort_order = section.sort_order
  } else {
    editingSection.value = null
    sectionForm.title = ''
    sectionForm.sort_order = footerSections.value.length
  }
  sectionFormOpen.value = true
}
function closeSectionForm() { sectionFormOpen.value = false; editingSection.value = null }

async function saveSection() {
  sectionSaving.value = true
  try {
    if (editingSection.value) {
      await adminApi.updateFooterSection(editingSection.value.id, { ...sectionForm })
      showSuccess('Section updated.')
    } else {
      await adminApi.createFooterSection({ ...sectionForm })
      showSuccess('Section created.')
    }
    closeSectionForm()
    await loadFooter()
  } catch (err) { showError(err instanceof Error ? err.message : 'Failed to save') }
  finally { sectionSaving.value = false }
}

async function deleteSection(id: number) {
  if (!confirm('Delete this section and all its links?')) return
  try {
    await adminApi.deleteFooterSection(id)
    showSuccess('Section deleted.')
    await loadFooter()
  } catch (err) { showError(err instanceof Error ? err.message : 'Failed to delete') }
}

function openLinkForm(sectionId: number, link?: AdminFooterLink) {
  if (link) {
    editingLink.value = link
    linkForm.label = link.label
    linkForm.url = link.url
    linkForm.external = link.external
    linkForm.sort_order = link.sort_order
    linkForm.footer_section_id = link.footer_section_id
  } else {
    editingLink.value = null
    const section = footerSections.value.find(s => s.id === sectionId)
    linkForm.label = ''
    linkForm.url = ''
    linkForm.external = false
    linkForm.sort_order = section?.links.length ?? 0
    linkForm.footer_section_id = sectionId
  }
  linkFormOpen.value = true
}
function closeLinkForm() { linkFormOpen.value = false; editingLink.value = null }

async function saveLink() {
  linkSaving.value = true
  try {
    if (editingLink.value) {
      await adminApi.updateFooterLink(editingLink.value.id, { ...linkForm })
      showSuccess('Link updated.')
    } else {
      await adminApi.createFooterLink({ ...linkForm })
      showSuccess('Link created.')
    }
    closeLinkForm()
    await loadFooter()
  } catch (err) { showError(err instanceof Error ? err.message : 'Failed to save') }
  finally { linkSaving.value = false }
}

async function deleteLink(id: number) {
  if (!confirm('Delete this link?')) return
  try {
    await adminApi.deleteFooterLink(id)
    showSuccess('Link deleted.')
    await loadFooter()
  } catch (err) { showError(err instanceof Error ? err.message : 'Failed to delete') }
}

// ── SMTP ────────────────────────────────────────────────────
const smtpTesting = ref(false)
const smtpTestResult = ref<{ success: boolean; message: string } | null>(null)

const smtpSettings = computed(() => settings.value.filter(s => s.group === 'smtp'))

async function testSmtp() {
  smtpTesting.value = true
  smtpTestResult.value = null
  try {
    const res = await adminApi.testSmtp()
    smtpTestResult.value = { success: res.success, message: res.message }
  } catch (err) {
    smtpTestResult.value = { success: false, message: err instanceof Error ? err.message : 'SMTP test failed' }
  } finally {
    smtpTesting.value = false
  }
}

// ── Security ────────────────────────────────────────────────
const securitySettings = computed(() => settings.value.filter(s => s.group === 'security'))

function getBlockedNetworksText(): string {
  const val = editValues.value['blocked_networks']
  if (Array.isArray(val)) return val.join('\n')
  if (typeof val === 'string') {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) return parsed.join('\n')
    } catch { /* not JSON, display as-is */ }
    return val
  }
  return ''
}

function setBlockedNetworksText(text: string) {
  const networks = text.split('\n').map(s => s.trim()).filter(s => s.length > 0)
  editValues.value['blocked_networks'] = networks as any
}

async function saveSettings() {
  saving.value = true
  try {
    const payload = settings.value.map(s => ({
      key: s.key,
      value: editValues.value[s.key] ?? null,
      type: s.type,
    }))
    await adminApi.updateSettings(payload)
    showSuccess('Settings saved successfully.')
  } catch (err) { showError(err instanceof Error ? err.message : 'Failed to save') }
  finally { saving.value = false }
}

onMounted(async () => {
  try {
    await Promise.all([loadSettings(), loadMenuItems(), loadFooter()])
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load settings'
  } finally { loading.value = false }
})
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">Settings</h2>
      <button v-if="activeTab === 'general' || activeTab === 'security'" :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50" @click="saveSettings">
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <div v-if="successMsg" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400">{{ successMsg }}</div>
    <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">{{ error }}</div>

    <div v-if="loading" class="py-16"><LoadingSpinner size="lg" label="Loading settings..." /></div>

    <template v-else>
      <!-- Tab bar -->
      <div class="mb-6 flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
        <button
          v-for="tab in tabs" :key="tab.key"
          class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors"
          :class="activeTab === tab.key ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
          @click="activeTab = tab.key"
        >{{ tab.label }}</button>
      </div>

      <!-- GENERAL TAB -->
      <div v-if="activeTab === 'general'" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Site Information</h3>
          </div>
          <div class="space-y-4 p-5">
            <div v-for="s in generalSettings" :key="s.key">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ getSettingLabel(s.key) }}</label>
              <p v-if="getSettingDesc(s.key)" class="mb-1.5 text-xs text-gray-500">{{ getSettingDesc(s.key) }}</p>
              <textarea v-if="s.type === 'text'" :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLTextAreaElement).value" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              <input v-else :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLInputElement).value" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
          </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Branding & Media</h3>
          </div>
          <div class="space-y-5 p-5">
            <div v-for="s in brandingSettings" :key="s.key">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ getSettingLabel(s.key) }}</label>
              <p v-if="getSettingDesc(s.key)" class="mb-1.5 text-xs text-gray-500">{{ getSettingDesc(s.key) }}</p>

              <!-- Textarea fields (footer_description, etc.) -->
              <textarea v-if="s.type === 'text'" :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLTextAreaElement).value" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />

              <!-- Image fields (site_logo, og_image, favicon) -->
              <div v-else-if="isImageField(s.key)" class="flex items-center gap-3">
                <img v-if="editValues[s.key]" :src="(editValues[s.key] as string)" :alt="getSettingLabel(s.key)" class="h-12 w-12 rounded border border-gray-200 object-contain dark:border-gray-700" @error="onImageError" />
                <input type="text" :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLInputElement).value" placeholder="Paste image URL or upload" class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                <label class="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" :class="{ 'pointer-events-none opacity-50': uploadingField === s.key }">
                  {{ uploadingField === s.key ? 'Uploading…' : 'Upload' }}
                  <input type="file" accept="image/*" class="hidden" @change="uploadImage(s.key, $event)" />
                </label>
              </div>

              <!-- Plain text fields (copyright_text, etc.) -->
              <input v-else :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLInputElement).value" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
          </div>
        </div>
        <!-- Cron / Scheduled Tasks Info -->
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Scheduled Tasks (Cron)</h3>
          </div>
          <div class="p-5">
            <p class="mb-3 text-xs text-gray-500">These tasks run automatically via the Laravel scheduler. Ensure <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">php artisan schedule:work</code> or a system cron is active.</p>
            <table class="w-full text-left text-sm">
              <thead>
                <tr class="text-xs uppercase tracking-wider text-gray-500">
                  <th class="pb-2">Task</th>
                  <th class="pb-2">Command</th>
                  <th class="pb-2">Schedule</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr>
                  <td class="py-2 text-gray-900 dark:text-white">Refresh Node Latency</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">lg:refresh-latency</td>
                  <td class="py-2 text-gray-700 dark:text-gray-300">Every 15 seconds</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-900 dark:text-white">Check Stale Nodes</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">lg:check-nodes</td>
                  <td class="py-2 text-gray-700 dark:text-gray-300">Every minute</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-900 dark:text-white">Cleanup Rate Limit Events</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">lg:cleanup-rate-limits</td>
                  <td class="py-2 text-gray-700 dark:text-gray-300">Daily</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-900 dark:text-white">Cleanup Security Events</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">lg:cleanup-security-events</td>
                  <td class="py-2 text-gray-700 dark:text-gray-300">Daily</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-900 dark:text-white">Cleanup Old Tests</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">lg:cleanup-tests</td>
                  <td class="py-2 text-gray-700 dark:text-gray-300">Weekly</td>
                </tr>
                <tr>
                  <td class="py-2 text-gray-900 dark:text-white">Purge Completed Tests</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">lg:purge-tests</td>
                  <td class="py-2 text-gray-700 dark:text-gray-300">Daily</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- END GENERAL TAB -->

      <!-- HEADER TAB -->
      <div v-if="activeTab === 'header'">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Navigation Menu Items</h3>
            <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-500" @click="openMenuForm()">+ Add Item</button>
          </div>
          <div v-if="menuItems.length === 0" class="px-5 py-10 text-center text-sm text-gray-500">No menu items yet.</div>
          <table v-else class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500 dark:border-gray-700">
                <th class="px-5 py-3">Order</th>
                <th class="px-5 py-3">Label</th>
                <th class="px-5 py-3">URL</th>
                <th class="px-5 py-3 text-center">New Tab</th>
                <th class="px-5 py-3 text-center">Active</th>
                <th class="px-5 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in menuItems" :key="item.id" class="border-b border-gray-100 dark:border-gray-800">
                <td class="px-5 py-3 font-mono text-gray-500">{{ item.sort_order }}</td>
                <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ item.label }}</td>
                <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ item.url }}</td>
                <td class="px-5 py-3 text-center">
                  <span v-if="item.open_new_tab" class="text-primary-600">✓</span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span :class="item.active ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">{{ item.active ? '✓' : '—' }}</span>
                </td>
                <td class="px-5 py-3 text-right">
                  <button class="mr-2 text-xs text-primary-600 hover:underline dark:text-primary-400" @click="openMenuForm(item)">Edit</button>
                  <button class="text-xs text-red-600 hover:underline dark:text-red-400" @click="deleteMenuItem(item.id)">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Menu Item Modal -->
        <div v-if="menuFormOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeMenuForm">
          <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900 dark:border dark:border-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ editingMenu ? 'Edit' : 'Add' }} Menu Item</h3>
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Label</label>
                <input v-model="menuForm.label" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
                <input v-model="menuForm.url" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order</label>
                <input v-model.number="menuForm.sort_order" type="number" min="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
              <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                  <input v-model="menuForm.open_new_tab" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 dark:border-gray-600" />
                  Open in new tab
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                  <input v-model="menuForm.active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 dark:border-gray-600" />
                  Active
                </label>
              </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
              <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeMenuForm">Cancel</button>
              <button :disabled="menuSaving || !menuForm.label || !menuForm.url" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50" @click="saveMenuItem">
                {{ menuSaving ? 'Saving...' : (editingMenu ? 'Update' : 'Create') }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- END HEADER TAB -->

      <!-- FOOTER TAB -->
      <div v-if="activeTab === 'footer'" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Footer Text</h3>
          </div>
          <div class="space-y-4 p-5">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Footer Description</label>
              <p class="mb-1.5 text-xs text-gray-500">Short paragraph shown under the logo in the footer.</p>
              <textarea :value="String(editValues['footer_description'] ?? '')" @input="editValues['footer_description'] = ($event.target as HTMLTextAreaElement).value" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Copyright Text</label>
              <p class="mb-1.5 text-xs text-gray-500">Leave empty to auto-generate.</p>
              <input :value="String(editValues['copyright_text'] ?? '')" @input="editValues['copyright_text'] = ($event.target as HTMLInputElement).value" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div class="flex justify-end">
              <button :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50" @click="saveSettings">
                {{ saving ? 'Saving...' : 'Save Text' }}
              </button>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400">Footer Sections</h3>
          <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-500" @click="openSectionForm()">+ Add Section</button>
        </div>

        <div v-if="footerSections.length === 0" class="rounded-lg border border-dashed border-gray-300 py-10 text-center text-sm text-gray-500 dark:border-gray-700">No footer sections yet.</div>

        <div v-for="section in footerSections" :key="section.id" class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="flex cursor-pointer items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-800" @click="expandedSection = expandedSection === section.id ? null : section.id">
            <div class="flex items-center gap-3">
              <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-90': expandedSection === section.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ section.title }}</span>
              <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800">{{ section.links.length }} links</span>
            </div>
            <div class="flex items-center gap-2" @click.stop>
              <button class="text-xs text-primary-600 hover:underline dark:text-primary-400" @click="openSectionForm(section)">Edit</button>
              <button class="text-xs text-red-600 hover:underline dark:text-red-400" @click="deleteSection(section.id)">Delete</button>
            </div>
          </div>
          <div v-if="expandedSection === section.id" class="p-5">
            <div v-if="section.links.length === 0" class="mb-3 text-xs text-gray-500">No links in this section.</div>
            <table v-else class="mb-3 w-full text-left text-sm">
              <thead>
                <tr class="text-xs uppercase tracking-wider text-gray-500">
                  <th class="pb-2">Label</th>
                  <th class="pb-2">URL</th>
                  <th class="pb-2 text-center">External</th>
                  <th class="pb-2 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="link in section.links" :key="link.id" class="border-t border-gray-100 dark:border-gray-800">
                  <td class="py-2 text-gray-900 dark:text-white">{{ link.label }}</td>
                  <td class="py-2 font-mono text-xs text-gray-600 dark:text-gray-400">{{ link.url }}</td>
                  <td class="py-2 text-center">
                    <span v-if="link.external" class="text-primary-600">✓</span>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                  <td class="py-2 text-right">
                    <button class="mr-2 text-xs text-primary-600 hover:underline dark:text-primary-400" @click="openLinkForm(section.id, link)">Edit</button>
                    <button class="text-xs text-red-600 hover:underline dark:text-red-400" @click="deleteLink(link.id)">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <button class="rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs text-gray-500 hover:border-primary-400 hover:text-primary-600 dark:border-gray-700" @click="openLinkForm(section.id)">+ Add Link</button>
          </div>
        </div>
      </div>
      <!-- END FOOTER TAB -->

      <!-- SMTP TAB -->
      <div v-if="activeTab === 'smtp'" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">SMTP Configuration</h3>
          </div>
          <div class="space-y-4 p-5">
            <p class="text-xs text-gray-500">Configure your outgoing mail server for password reset emails and notifications.</p>
            <div v-if="smtpSettings.length === 0" class="py-8 text-center text-sm text-gray-500">
              No SMTP settings found. Run the settings seeder to create them.
            </div>
            <template v-for="s in smtpSettings" :key="s.key">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ s.label ?? s.key.replace(/_/g, ' ') }}
                </label>
                <p v-if="s.description" class="mb-1.5 text-xs text-gray-500">{{ s.description }}</p>
                <input v-if="s.key.includes('password')" type="password" :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLInputElement).value"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                <select v-else-if="s.key === 'smtp_encryption'" :value="String(editValues[s.key] ?? '')" @change="editValues[s.key] = ($event.target as HTMLSelectElement).value"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                  <option value="">None</option>
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                </select>
                <input v-else type="text" :value="String(editValues[s.key] ?? '')" @input="editValues[s.key] = ($event.target as HTMLInputElement).value"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
              </div>
            </template>

            <div v-if="smtpTestResult" :class="smtpTestResult.success ? 'rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400' : 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400'">
              {{ smtpTestResult.message }}
            </div>

            <div class="flex justify-between">
              <button :disabled="smtpTesting" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 disabled:opacity-50" @click="testSmtp">
                {{ smtpTesting ? 'Testing...' : 'Send Test Email' }}
              </button>
              <button :disabled="saving" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50" @click="saveSettings">
                {{ saving ? 'Saving...' : 'Save SMTP Settings' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- END SMTP TAB -->

      <!-- SECURITY TAB -->
      <div v-if="activeTab === 'security'" class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Security Settings</h3>
          </div>
          <div class="space-y-5 p-5">
            <p class="text-xs text-gray-500">Configure rate limiting, IP blocking, and security protections. Changes take effect immediately.</p>

            <div v-if="securitySettings.length === 0" class="py-8 text-center text-sm text-gray-500">
              No security settings found. Run the settings seeder to create them.
            </div>

            <!-- Rate Limit -->
            <div v-if="editValues['rate_limit_per_minute'] !== undefined">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Rate Limit (per minute)</label>
              <p class="mb-1.5 text-xs text-gray-500">Maximum test submissions per visitor per minute.</p>
              <input type="number" min="1" max="1000"
                :value="Number(editValues['rate_limit_per_minute'] ?? 30)"
                @input="editValues['rate_limit_per_minute'] = Number(($event.target as HTMLInputElement).value)"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <!-- Blocked Networks -->
            <div v-if="editValues['blocked_networks'] !== undefined">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Blocked Networks</label>
              <p class="mb-1.5 text-xs text-gray-500">CIDR ranges that cannot be targeted (private/reserved IPs). One per line.</p>
              <textarea rows="8"
                :value="getBlockedNetworksText()"
                @input="setBlockedNetworksText(($event.target as HTMLTextAreaElement).value)"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                placeholder="10.0.0.0/8&#10;172.16.0.0/12&#10;192.168.0.0/16"></textarea>
            </div>

            <!-- DNS Rebind Protection -->
            <div v-if="editValues['dns_rebind_protection'] !== undefined">
              <label class="flex items-center gap-3 cursor-pointer">
                <div class="relative">
                  <input type="checkbox" class="sr-only"
                    :checked="String(editValues['dns_rebind_protection']) === '1' || editValues['dns_rebind_protection'] === true"
                    @change="editValues['dns_rebind_protection'] = ($event.target as HTMLInputElement).checked ? '1' : '0'" />
                  <div class="h-6 w-11 rounded-full bg-gray-200 transition-colors dark:bg-gray-700"
                    :class="{ '!bg-primary-600': String(editValues['dns_rebind_protection']) === '1' || editValues['dns_rebind_protection'] === true }"></div>
                  <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform"
                    :class="{ 'translate-x-5': String(editValues['dns_rebind_protection']) === '1' || editValues['dns_rebind_protection'] === true }"></div>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">DNS Rebind Protection</span>
                  <p class="text-xs text-gray-500">Block hostnames that resolve to private/reserved IPs.</p>
                </div>
              </label>
            </div>

            <!-- Log Retention -->
            <div v-if="editValues['log_retention_days'] !== undefined">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Log Retention (days)</label>
              <p class="mb-1.5 text-xs text-gray-500">Number of days to keep rate limit and security event logs.</p>
              <input type="number" min="1" max="365"
                :value="Number(editValues['log_retention_days'] ?? 30)"
                @input="editValues['log_retention_days'] = Number(($event.target as HTMLInputElement).value)"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <!-- Max Output Bytes -->
            <div v-if="editValues['max_output_bytes'] !== undefined">
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Max Output Size (bytes)</label>
              <p class="mb-1.5 text-xs text-gray-500">Maximum output size for test results. Default: 1048576 (1 MB).</p>
              <input type="number" min="1024" max="10485760"
                :value="Number(editValues['max_output_bytes'] ?? 1048576)"
                @input="editValues['max_output_bytes'] = Number(($event.target as HTMLInputElement).value)"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
          </div>
        </div>
      </div>
      <!-- END SECURITY TAB -->

      <!-- Section Modal -->
      <div v-if="sectionFormOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeSectionForm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900 dark:border dark:border-gray-800">
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ editingSection ? 'Edit' : 'Add' }} Section</h3>
          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
              <input v-model="sectionForm.title" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order</label>
              <input v-model.number="sectionForm.sort_order" type="number" min="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" @click="closeSectionForm">Cancel</button>
            <button :disabled="sectionSaving || !sectionForm.title" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50" @click="saveSection">
              {{ sectionSaving ? 'Saving...' : (editingSection ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Link Modal -->
      <div v-if="linkFormOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeLinkForm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900 dark:border dark:border-gray-800">
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ editingLink ? 'Edit' : 'Add' }} Link</h3>
          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Label</label>
              <input v-model="linkForm.label" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
              <input v-model="linkForm.url" type="text" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order</label>
              <input v-model.number="linkForm.sort_order" type="number" min="0" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input v-model="linkForm.external" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 dark:border-gray-600" />
              External link (opens in new tab)
            </label>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" @click="closeLinkForm">Cancel</button>
            <button :disabled="linkSaving || !linkForm.label || !linkForm.url" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50" @click="saveLink">
              {{ linkSaving ? 'Saving...' : (editingLink ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
