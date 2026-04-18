import { createApp } from "vue";
import { createPinia } from "pinia";
import { watch } from "vue";
import { useAuthStore } from "@/stores/auth";
import { initEcho, destroyEcho, getEcho } from "@/plugins/echo";
import App from "./App.vue";
import router from "./router";
import "./style.css";
import "./assets/main.css";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const authStore = useAuthStore();

// 1. Khởi tạo auth (restore token từ localStorage)
authStore.initAuth().finally(() => {
  // 2. LUÔN mount app dù auth thành công hay thất bại
  app.mount("#app");

  // 3. Khởi tạo Echo SAU khi mount — lỗi Echo không làm trắng trang
  if (authStore.isAuthenticated && authStore.token) {
    safeInitEcho(authStore.token);
  }
});

// Theo dõi thay đổi auth state để init/destroy Echo
// Chỉ init 1 lần — không disconnect/reconnect khi chuyển route
watch(
  () => authStore.isAuthenticated,
  (authenticated) => {
    if (authenticated && authStore.token) {
      // Chỉ init nếu chưa có Echo instance
      if (!getEcho()) {
        safeInitEcho(authStore.token);
      }
    } else {
      destroyEcho();
    }
  },
);

/**
 * Khởi tạo Echo an toàn — lỗi kết nối WebSocket KHÔNG crash app.
 * Reverb có thể chưa khởi động khi dev → không nên block render.
 */
function safeInitEcho(token) {
  try {
    initEcho(token);
  } catch (err) {
    console.warn("[Echo] Không thể kết nối WebSocket:", err?.message ?? err);
  }
}
