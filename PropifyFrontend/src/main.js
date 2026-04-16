import { createApp } from "vue";
import { createPinia } from "pinia";
import { watch } from "vue";
import { useAuthStore } from "@/stores/auth";
import { initEcho, destroyEcho } from "@/plugins/echo";
import App from "./App.vue";
import router from "./router";
import "./style.css";
import "./assets/main.css";

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const authStore = useAuthStore();
authStore.initAuth().finally(() => {
  // Khởi tạo Echo ngay sau auth nếu đã login
  if (authStore.isAuthenticated && authStore.token) {
    initEcho(authStore.token);
  }
  app.mount("#app");
});

// Lắng nghe thay đổi auth để init/destroy Echo
watch(
  () => authStore.isAuthenticated,
  (authenticated) => {
    if (authenticated && authStore.token) {
      initEcho(authStore.token);
    } else {
      destroyEcho();
    }
  },
);
