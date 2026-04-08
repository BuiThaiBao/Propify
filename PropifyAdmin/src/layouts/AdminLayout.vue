<script setup>
import { RouterView } from 'vue-router'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopbar from '@/components/layout/AppTopbar.vue'
import { useAdminStore } from '@/stores/admin'

const admin = useAdminStore()
</script>

<template>
  <div class="flex min-h-screen bg-background">
    <AppSidebar />
    <!-- Main content shifts based on sidebar width -->
    <div
      class="flex flex-col flex-1 min-w-0 transition-all duration-300"
      :style="{ marginLeft: admin.sidebarCollapsed ? '72px' : '260px' }"
    >
      <AppTopbar />
      <main class="flex-1 p-6 overflow-y-auto">
        <RouterView />
      </main>
    </div>
  </div>
</template>
