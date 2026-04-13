<template>
  <nav
    class="fixed top-0 left-0 right-0 z-50 hero-gradient border-b border-white/20"
  >
    <div class="container mx-auto flex items-center justify-between h-16 px-4">
      <!-- Logo -->
      <a href="/" class="flex items-center gap-2">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="32" height="32" rx="12" fill="white" fill-opacity="0.25"/>
          <path d="M21.3337 14.6667C21.3337 17.9953 17.641 21.462 16.401 22.5327C16.2855 22.6195 16.1449 22.6665 16.0003 22.6665C15.8558 22.6665 15.7152 22.6195 15.5997 22.5327C14.3597 21.462 10.667 17.9953 10.667 14.6667C10.667 13.2522 11.2289 11.8956 12.2291 10.8954C13.2293 9.89525 14.5858 9.33334 16.0003 9.33334C17.4148 9.33334 18.7714 9.89525 19.7716 10.8954C20.7718 11.8956 21.3337 13.2522 21.3337 14.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16 16.6667C17.1046 16.6667 18 15.7712 18 14.6667C18 13.5621 17.1046 12.6667 16 12.6667C14.8954 12.6667 14 13.5621 14 14.6667C14 15.7712 14.8954 16.6667 16 16.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-xl font-bold text-white">Propify</span>
      </a>

      <!-- Menu desktop -->
      <div class="hidden md:flex items-center gap-1">
        <a
          v-for="item in navLinks"
          :key="item.href"
          :href="item.href"
          :class="isActive(item.href)
            ? 'px-4 py-2 rounded-lg text-sm font-medium text-white bg-white/25'
            : 'px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/15'"
        >
          {{ item.label }}
        </a>
      </div>

      <!-- Actions desktop -->
      <div class="hidden md:flex items-center gap-2">
        <!-- Heart icon (SVG outline) -->
      
        <!-- Chưa đăng nhập: hiện link text "Đăng nhập" -->
        <template v-if="!authStore.isAuthenticated">
          <button
            @click="showLoginPopup = true"
            class="text-sm font-medium text-white/90 hover:text-white underline underline-offset-2 transition-colors px-2 py-2"
          >
            Đăng nhập
          </button>
        </template>

        <!-- Đã đăng nhập: hiện Account icon với dropdown -->
        <template v-else>
          <div class="relative" ref="accountDropdownRef">
            <button
              class="p-2 text-white/80 hover:text-white transition-colors rounded-lg hover:bg-white/15"
              title="Yêu thích"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path
                  d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                />
              </svg>
            </button>
            <button
              @click="accountMenuOpen = !accountMenuOpen"
              class="p-2 text-white/80 hover:text-white transition-colors rounded-lg hover:bg-white/15"
              title="Tài khoản"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </button>

            <!-- Dropdown -->
            <div
              v-if="accountMenuOpen"
              class="absolute right-0 top-full mt-2 w-52 bg-card border border-border rounded-xl shadow-lg py-1 z-50"
            >
              <div class="px-4 py-2.5 border-b border-border/50">
                <p class="text-sm font-medium text-foreground truncate">
                  {{ authStore.user?.full_name }}
                </p>
                <p class="text-xs text-muted-foreground truncate">
                  {{ authStore.user?.email }}
                </p>
              </div>
              <router-link
                to="/profile"
                @click="accountMenuOpen = false"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                  <circle cx="12" cy="7" r="4"/>
                </svg>
                Thông tin tài khoản
              </router-link>
              <button
                @click="handleLogout"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                  <polyline points="16 17 21 12 16 7"/>
                  <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Đăng xuất
              </button>
            </div>
          </div>
        </template>

        <!-- Đăng tin button (luôn hiện) -->
        <button
          @click="handlePostListing"
          class="bg-white text-[#0DA2E7] px-4 py-2 rounded-lg hover:bg-white/90 active:scale-[0.97] transition-all font-semibold text-sm ml-1 shadow-sm"
        >
          Đăng tin
        </button>
      </div>

      <!-- Mobile menu button -->
      <button
        class="md:hidden p-2 text-white"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        ☰
      </button>
    </div>

    <!-- Mobile menu -->
    <div v-if="mobileMenuOpen" class="md:hidden border-t border-border bg-card">
      <div class="p-4 flex flex-col gap-1">
        <a
          v-for="item in navLinks"
          :key="item.href"
          :href="item.href"
          :class="isActive(item.href)
            ? 'px-4 py-3 rounded-lg text-sm font-medium text-primary bg-primary/10'
            : 'px-4 py-3 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted'"
          @click="mobileMenuOpen = false"
        >
          {{ item.label }}
        </a>

        <div class="flex flex-col gap-2 mt-3 pt-3 border-t border-border">
          <template v-if="!authStore.isAuthenticated">
            <button
              @click="showLoginPopup = true; mobileMenuOpen = false"
              class="w-full border border-primary/30 text-primary hover:bg-primary/10 rounded-lg py-3 font-medium text-center transition-colors"
            >
              Đăng nhập
            </button>
          </template>
          <template v-else>
            <div class="py-2 px-3 border rounded-lg bg-muted/20 flex items-center gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="text-muted-foreground shrink-0"
              >
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <span class="text-sm font-medium text-foreground truncate">
                {{ authStore.user?.full_name }}
              </span>
            </div>
            <button
              @click="handleLogout(); mobileMenuOpen = false"
              class="w-full border border-destructive/20 text-destructive hover:bg-destructive/10 rounded-lg py-3 font-medium text-center transition-colors"
            >
              Đăng xuất
            </button>
          </template>
          <button
            @click="handlePostListing(); mobileMenuOpen = false"
            class="w-full hero-gradient text-primary-foreground rounded-lg py-3 font-medium text-center"
          >
            Đăng tin
          </button>
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
import { ref, onMounted, onUnmounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter, useRoute } from "vue-router";
import Login from "@/pages/Auth/Login.vue";
import Register from "@/pages/Auth/Register.vue";

const mobileMenuOpen = ref(false);
const accountMenuOpen = ref(false);
const accountDropdownRef = ref(null);
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const navLinks = [
  { label: "Trang chủ", href: "/" },
  { label: "Mua bán", href: "/sales" },
  { label: "Cho thuê", href: "/rent" },
  { label: "Tin tức", href: "/news" },
  { label: "Liên hệ", href: "/contact" },
];

function isActive(href) {
  if (href === "/") return route.path === "/";
  return route.path.startsWith(href);
}

const showLoginPopup = ref(false);
const showRegisterPopup = ref(false);

function handlePostListing() {
  if (!authStore.isAuthenticated) {
    showLoginPopup.value = true;
    return;
  }
  // Chưa cập nhật SĐT → chuyển sang trang profile để bổ sung
  if (!authStore.user?.phone) {
    router.push({ name: "Profile", query: { require: "phone" } });
    return;
  }
  router.push("/post-listing");
}

function handleAuthSuccess() {
  showLoginPopup.value = false;
  showRegisterPopup.value = false;
  router.push("/");
}

async function handleLogout() {
  accountMenuOpen.value = false;
  await authStore.logout();
  router.push("/");
}

function handleClickOutside(e) {
  if (
    accountDropdownRef.value &&
    !accountDropdownRef.value.contains(e.target)
  ) {
    accountMenuOpen.value = false;
  }
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));
</script>

<style lang="scss" scoped></style>
