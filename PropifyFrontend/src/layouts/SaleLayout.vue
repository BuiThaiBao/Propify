<template>
  <div class="min-h-screen bg-white flex flex-col font-sans text-gray-900">
    <AppHeader />
    <main class="flex-1 bg-gray-50/50 pt-16">
      <slot />
    </main>
    <AppFooter />
  </div>
</template>

<script setup>
import AppHeader from "@/components/common/AppHeader.vue";
import AppFooter from "@/components/common/AppFooter.vue";
</script>
