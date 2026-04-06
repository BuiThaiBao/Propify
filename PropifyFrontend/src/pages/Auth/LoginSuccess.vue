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
  const token = route.query.token;

  if (token) {
    try {
      await authStore.setTokenFromGoogle(token);
      router.replace("/");
    } catch {
      router.replace("/login");
    }
  } else {
    router.replace("/login");
  }
});
</script>
