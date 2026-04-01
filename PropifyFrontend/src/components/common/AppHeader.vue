<template>
  <nav
    class="fixed top-0 left-0 right-0 z-50 bg-card/80 backdrop-blur-xl border-b border-border/50"
  >
    <div class="container mx-auto flex items-center justify-between h-16 px-4">
      <!-- Logo -->
      <a href="/" class="flex items-center gap-2">
        <div
          class="w-8 h-8 rounded-lg hero-gradient flex items-center justify-center"
        >
          <span class="w-4 h-4 bg-white rounded-sm"></span>
        </div>
        <span class="text-xl font-bold text-foreground">RentHouse</span>
      </a>

      <!-- Menu desktop -->
      <div class="hidden md:flex items-center gap-1">
        <a
          href="#"
          class="px-4 py-2 rounded-lg text-sm font-medium text-primary bg-primary/10"
        >
          Trang chủ
        </a>
        <a
          href="#"
          class="px-4 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted"
        >
          Cho thuê
        </a>
        <a
          href="#"
          class="px-4 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted"
        >
          Tin tức
        </a>
        <a
          href="#"
          class="px-4 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted"
        >
          Liên hệ
        </a>
      </div>

      <!-- Actions desktop -->
      <div class="hidden md:flex items-center gap-3">
        <button class="text-muted-foreground p-2 hover:text-foreground transition-colors">
          ❤️
        </button>
        
        <template v-if="!authStore.isAuthenticated">
          <button
            @click="handlePostListing"
            class="hero-gradient text-primary-foreground px-4 py-2 rounded-lg hover:opacity-90 active:scale-[0.97] transition-all font-medium"
          >
            Đăng tin
          </button>
        </template>
        
        <template v-else>
          <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-foreground">
              {{ authStore.user?.full_name }}
            </span>
            <button
              @click="handleLogout"
              class="text-sm text-muted-foreground hover:text-destructive transition-colors"
            >
              Đăng xuất
            </button>
            <button
              @click="handlePostListing"
              class="hero-gradient text-primary-foreground px-4 py-2 rounded-lg hover:opacity-90 active:scale-[0.97] transition-all font-medium"
            >
              Đăng tin
            </button>
          </div>
        </template>
      </div>

      <!-- Mobile menu button -->
      <button
        class="md:hidden p-2 text-foreground"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        ☰
      </button>
    </div>

    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="md:hidden border-t border-border bg-card">
      <div class="p-4 flex flex-col gap-1">
        <a
          href="#"
          class="px-4 py-3 rounded-lg text-sm font-medium text-primary bg-primary/10"
        >
          Trang chủ
        </a>
        <a
          href="#"
          class="px-4 py-3 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted"
        >
          Cho thuê
        </a>
        <a
          href="#"
          class="px-4 py-3 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted"
        >
          Tin tức
        </a>

        <div class="flex flex-col gap-2 mt-3 pt-3 border-t border-border">
          <template v-if="!authStore.isAuthenticated">
            <button
              @click="handlePostListing(); mobileMenuOpen = false"
              class="w-full hero-gradient text-primary-foreground rounded-lg py-3 font-medium text-center"
            >
              Đăng tin
            </button>
          </template>
          <template v-else>
             <div class="py-2 text-center border rounded-lg bg-muted/20">
              <span class="text-sm font-medium text-foreground">
                {{ authStore.user?.full_name }}
              </span>
            </div>
            <button
              @click="handlePostListing(); mobileMenuOpen = false"
              class="w-full hero-gradient text-primary-foreground rounded-lg py-3 font-medium text-center"
            >
              Đăng tin
            </button>
            <button
              @click="handleLogout(); mobileMenuOpen = false"
              class="w-full border border-destructive/20 text-destructive hover:bg-destructive/10 rounded-lg py-3 font-medium text-center transition-colors"
            >
              Đăng xuất
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Popups -->
    <Login
      v-if="showLoginPopup"
      @close="showLoginPopup = false"
      @success="handleAuthSuccess"
      @switchToRegister="showLoginPopup = false; showRegisterPopup = true"
    />
    <Register
      v-if="showRegisterPopup"
      @close="showRegisterPopup = false"
      @success="handleAuthSuccess"
      @switchToLogin="showRegisterPopup = false; showLoginPopup = true"
    />
  </nav>
</template>

<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import Login from "@/pages/Login.vue";
import Register from "@/pages/Register.vue";

const mobileMenuOpen = ref(false);
const authStore = useAuthStore();
const router = useRouter();

const showLoginPopup = ref(false);
const showRegisterPopup = ref(false);

function handlePostListing() {
  if (!authStore.isAuthenticated) {
    // Chưa login → hiện popup đăng nhập
    showLoginPopup.value = true;
    return;
  }
  // Đã login → đi tới trang đăng tin
  router.push("/post-listing");
}

function handleAuthSuccess() {
  showLoginPopup.value = false;
  showRegisterPopup.value = false;
  // Người dùng đăng nhập thành công vì họ click Đăng tin, nên ta chuyển hướng họ
  router.push("/post-listing");
}

async function handleLogout() {
  await authStore.logout();
  router.push("/");
}
</script>

<style lang="scss" scoped></style>
