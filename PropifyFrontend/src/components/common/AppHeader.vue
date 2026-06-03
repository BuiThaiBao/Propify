<template>
  <nav
    class="fixed top-0 left-0 right-0 z-50 hero-gradient border-b border-white/20"
  >
    <div class="container mx-auto flex items-center justify-between h-16 px-4">
      <!-- Logo -->
      <router-link to="/" class="flex items-center gap-2">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="32" height="32" rx="12" fill="white" fill-opacity="0.25"/>
          <path d="M21.3337 14.6667C21.3337 17.9953 17.641 21.462 16.401 22.5327C16.2855 22.6195 16.1449 22.6665 16.0003 22.6665C15.8558 22.6665 15.7152 22.6195 15.5997 22.5327C14.3597 21.462 10.667 17.9953 10.667 14.6667C10.667 13.2522 11.2289 11.8956 12.2291 10.8954C13.2293 9.89525 14.5858 9.33334 16.0003 9.33334C17.4148 9.33334 18.7714 9.89525 19.7716 10.8954C20.7718 11.8956 21.3337 13.2522 21.3337 14.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16 16.6667C17.1046 16.6667 18 15.7712 18 14.6667C18 13.5621 17.1046 12.6667 16 12.6667C14.8954 12.6667 14 13.5621 14 14.6667C14 15.7712 14.8954 16.6667 16 16.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="text-xl font-bold text-white">Propify</span>
      </router-link>

      <!-- Menu desktop -->
      <div class="hidden md:flex items-center gap-1">
        <router-link
          v-for="item in navLinks"
          :key="item.href"
          :to="item.href"
          :class="isActive(item.href)
            ? 'px-4 py-2 rounded-lg text-sm font-medium text-white bg-white/25'
            : 'px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/15'"
        >
          {{ item.label }}
        </router-link>
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
          <div class="relative flex items-center gap-1" ref="accountDropdownRef">
            <router-link
              to="/profile?tab=favorites"
              @click="accountMenuOpen = false"
              class="inline-flex h-9 w-9 items-center justify-center text-white/80 hover:text-white transition-colors rounded-lg hover:bg-white/15"
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
            </router-link>
            <div class="relative">
              <button
                @click="toggleNotificationMenu"
                class="inline-flex h-9 w-9 items-center justify-center text-white/80 hover:text-white transition-colors rounded-lg hover:bg-white/15 relative"
                title="Thông báo"
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
                  <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                  <path d="M9 17a3 3 0 0 0 6 0" />
                </svg>
                <span
                  v-if="unreadCount > 0"
                  class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[11px] font-semibold flex items-center justify-center"
                >
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </button>

              <div
                v-if="notificationMenuOpen"
                class="absolute right-0 top-full mt-2 w-80 bg-card border border-border rounded-xl shadow-lg z-50 overflow-hidden"
              >
                <div class="px-4 py-3 border-b border-border/50 flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-foreground">Thông báo</p>
                    <p class="text-xs text-muted-foreground">{{ unreadCount }} chưa đọc</p>
                  </div>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllNotificationsRead"
                    class="text-xs font-medium text-sky-600 hover:text-sky-700"
                  >
                    Đọc tất cả
                  </button>
                </div>

                <div v-if="notificationLoading" class="px-4 py-6 text-sm text-muted-foreground text-center">
                  Đang tải thông báo...
                </div>

                <div v-else-if="notifications.length === 0" class="px-4 py-6 text-sm text-muted-foreground text-center">
                  Chưa có thông báo nào.
                </div>

                <div v-else class="max-h-96 overflow-y-auto">
                  <button
                    v-for="item in notifications"
                    :key="item.id"
                    @click="handleNotificationClick(item)"
                    class="w-full text-left px-4 py-3 border-b border-border/40 hover:bg-muted/40 transition-colors"
                  >
                    <div class="flex items-start gap-3">
                      <span
                        class="mt-1 h-2.5 w-2.5 rounded-full shrink-0"
                        :class="item.read_at ? 'bg-slate-300' : 'bg-sky-500'"
                      ></span>
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-foreground">{{ item.title }}</p>
                        <p class="text-xs text-muted-foreground mt-1 line-clamp-2">{{ item.content }}</p>
                        <p class="text-[11px] text-muted-foreground/80 mt-2">{{ formatNotificationTime(item.created_at) }}</p>
                      </div>
                    </div>
                  </button>
                </div>

                <router-link
                  to="/profile?tab=notifications"
                  @click="notificationMenuOpen = false; accountMenuOpen = false"
                  class="block px-4 py-3 text-sm font-medium text-sky-600 hover:bg-sky-50"
                >
                  Xem tất cả thông báo
                </router-link>
              </div>
            </div>
            <button
              @click="accountMenuOpen = !accountMenuOpen"
              class="inline-flex h-9 w-9 items-center justify-center text-white/80 hover:text-white transition-colors rounded-lg hover:bg-white/15"
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
              <router-link
                to="/profile?tab=listings"
                @click="accountMenuOpen = false"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
                </svg>
                Quản lý tin đăng
              </router-link>
              <router-link
                to="/profile?tab=appointments"
                @click="accountMenuOpen = false"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                </svg>
                Quản lý đặt lịch
              </router-link>
              <router-link
                to="/profile?tab=favorites"
                @click="accountMenuOpen = false"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                Tin đăng yêu thích
              </router-link>
              <router-link
                to="/profile?tab=viewed"
                @click="accountMenuOpen = false"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                Tin đã xem
              </router-link>
              <router-link
                to="/profile?tab=transactions"
                @click="accountMenuOpen = false"
                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                Lịch sử giao dịch
              </router-link>
              
              <div class="border-t border-border/50 my-1"></div>
              
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
        <router-link
          v-for="item in navLinks"
          :key="item.href"
          :to="item.href"
          :class="isActive(item.href)
            ? 'px-4 py-3 rounded-lg text-sm font-medium text-primary bg-primary/10'
            : 'px-4 py-3 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted'"
          @click="mobileMenuOpen = false"
        >
          {{ item.label }}
        </router-link>

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

    <div class="fixed top-20 right-4 z-[60] flex flex-col gap-2 w-[320px] pointer-events-none">
      <div
        v-for="toast in notificationToasts"
        :key="toast.id"
        class="pointer-events-auto rounded-xl border border-sky-100 bg-white/95 px-4 py-3 shadow-lg backdrop-blur"
      >
        <p class="text-sm font-semibold text-slate-800">{{ toast.title }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ toast.content }}</p>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter, useRoute } from "vue-router";
import { getEcho } from "@/plugins/echo";
import notificationService from "@/services/notificationService";
import Login from "@/pages/Auth/Login.vue";
import Register from "@/pages/Auth/Register.vue";

const mobileMenuOpen = ref(false);
const accountMenuOpen = ref(false);
const notificationMenuOpen = ref(false);
const accountDropdownRef = ref(null);
const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const notifications = ref([]);
const unreadCount = ref(0);
const notificationLoading = ref(false);
const notificationToasts = ref([]);
let subscribedChannelName = null;
let toastId = 1;

const navLinks = [
  { label: "Trang chủ", href: "/" },
  { label: "Mua bán", href: "/sales" },
  { label: "Cho thuê", href: "/rent" },
  { label: "Bảng giá", href: "/pricing" },
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
  leaveNotificationChannel();
  accountMenuOpen.value = false;
  notificationMenuOpen.value = false;
  await authStore.logout();
  router.push("/");
}

function handleClickOutside(e) {
  if (
    accountDropdownRef.value &&
    !accountDropdownRef.value.contains(e.target)
  ) {
    accountMenuOpen.value = false;
    notificationMenuOpen.value = false;
  }
}

function formatNotificationTime(value) {
  if (!value) return "";
  return new Date(value).toLocaleString("vi-VN");
}

function pushToast(notification) {
  const id = toastId++;
  notificationToasts.value = [
    ...notificationToasts.value,
    { id, title: notification.title, content: notification.content },
  ];

  window.setTimeout(() => {
    notificationToasts.value = notificationToasts.value.filter((item) => item.id !== id);
  }, 3500);
}

async function fetchNotifications() {
  if (!authStore.isAuthenticated) {
    notifications.value = [];
    unreadCount.value = 0;
    return;
  }

  notificationLoading.value = true;

  try {
    const response = await notificationService.getNotifications({ per_page: 6 });
    notifications.value = response?.data?.data || [];
    unreadCount.value = Number(response?.data?.meta?.unread_count || 0);
  } catch {
    notifications.value = [];
    unreadCount.value = 0;
  } finally {
    notificationLoading.value = false;
  }
}

async function markAllNotificationsRead() {
  try {
    await notificationService.markAllAsRead();
    notifications.value = notifications.value.map((item) => ({
      ...item,
      read_at: item.read_at || new Date().toISOString(),
    }));
    unreadCount.value = 0;
  } catch {
    // keep current state on failure
  }
}

async function handleNotificationClick(item) {
  if (!item.read_at) {
    try {
      await notificationService.markAsRead(item.id);
      item.read_at = new Date().toISOString();
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {
      // ignore click failure
    }
  }

  notificationMenuOpen.value = false;
  accountMenuOpen.value = false;
  router.push("/profile?tab=notifications");
}

function leaveNotificationChannel() {
  const echo = getEcho();
  if (echo && subscribedChannelName) {
    echo.leave(subscribedChannelName);
  }
  subscribedChannelName = null;
}

function subscribeNotifications() {
  const echo = getEcho();
  const userId = authStore.user?.id;

  if (!echo || !userId) {
    return;
  }

  const channelName = `user.${userId}`;

  if (subscribedChannelName === channelName) {
    return;
  }

  leaveNotificationChannel();

  echo.private(channelName).listen(".notification.sent", (event) => {
    const notification = event?.notification;
    if (!notification) {
      return;
    }

    notifications.value = [notification, ...notifications.value.filter((item) => item.id !== notification.id)].slice(0, 6);
    unreadCount.value += notification.read_at ? 0 : 1;
    pushToast(notification);
  });

  subscribedChannelName = channelName;
}

function toggleNotificationMenu() {
  notificationMenuOpen.value = !notificationMenuOpen.value;
  accountMenuOpen.value = false;

  if (notificationMenuOpen.value && notifications.value.length === 0) {
    fetchNotifications();
  }
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get("error") === "admin_not_allowed") {
    // Xóa query param để không hiện lại khi reload
    router.replace({ query: {} });
    setTimeout(() => {
      alert("Tài khoản quản trị không được phép đăng nhập tại đây.");
    }, 500);
  }
  document.addEventListener("click", handleClickOutside);
  if (authStore.isAuthenticated) {
    fetchNotifications();
    subscribeNotifications();
  }
});
onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
  leaveNotificationChannel();
});

watch(
  () => authStore.isAuthenticated,
  async (authenticated) => {
    if (authenticated) {
      await fetchNotifications();
      subscribeNotifications();
    } else {
      leaveNotificationChannel();
      notifications.value = [];
      unreadCount.value = 0;
    }
  },
);

watch(
  () => authStore.user?.id,
  () => {
    if (authStore.isAuthenticated) {
      subscribeNotifications();
    }
  },
);
</script>

