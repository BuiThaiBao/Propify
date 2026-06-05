<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppHeader from '@/components/common/AppHeader.vue';
import FloatingChat from '@/components/chat/floating/FloatingChat.vue';
import { useAuthStore } from '@/stores/auth';

const isEmbedded = ref(false);
const authStore = useAuthStore();

function handleMessage(event) {
  if (event.origin !== window.location.origin) return;
  if (event.data?.type === 'auth-success') {
    authStore.initAuth();
  }
}

onMounted(() => {
  isEmbedded.value = window.self !== window.top;
  window.addEventListener('message', handleMessage);
});

onUnmounted(() => {
  window.removeEventListener('message', handleMessage);
});
</script>

<template>
  <AppHeader v-if="!isEmbedded" />
  <router-view />
  <FloatingChat v-if="!isEmbedded" />
</template>
