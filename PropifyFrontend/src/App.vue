<script setup>
import AppHeader from '@/components/common/AppHeader.vue';
import FloatingChat from '@/components/chat/FloatingChat.vue';
</script>

<template>
  <AppHeader />
  <router-view />
  <FloatingChat />
</template>
