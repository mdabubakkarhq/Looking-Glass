<script setup lang="ts">
import AdminSidebar from '@/components/admin/AdminSidebar.vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
</script>

<template>
  <div class="flex min-h-screen bg-gray-50 dark:bg-gray-950">
    <AdminSidebar />
    <div class="flex flex-1 flex-col">
      <AdminHeader />
      <main class="flex-1 bg-gray-50 p-6 dark:bg-gray-950">
        <router-view />
      </main>
    </div>
  </div>
</template>
