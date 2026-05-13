<template>
  <div class="flex items-center justify-center min-h-screen">
    <p class="text-muted-foreground">Đang đăng nhập...</p>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

onMounted(async () => {
  if (route.query.error) {
    router.replace("/login");
    return;
  }

  try {
    await authStore.setTokenFromGoogle();
    router.replace("/");
  } catch {
    router.replace("/login");
  }
});
</script>
