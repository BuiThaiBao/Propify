<script setup>
import { computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAdminStore } from '@/stores/admin'
import {
  LayoutDashboard,
  FileText,
  Package,
  Users,
  Settings,
  BarChart3,
  ChevronLeft,
  Building2,
} from 'lucide-vue-next'

const route = useRoute()
const admin = useAdminStore()

const menuItems = [
  { title: 'Dashboard', url: '/', icon: LayoutDashboard },
  { title: 'Quản lý tin đăng', url: '/posts', icon: FileText },
  { title: 'Quản lý gói tin', url: '/packages', icon: Package },
  { title: 'Quản lý tài khoản', url: '/users', icon: Users },
  { title: 'Tiện ích hệ thống', url: '/utilities', icon: Settings },
  { title: 'Doanh thu & báo cáo', url: '/revenue', icon: BarChart3 },
]

function isActive(url) {
  if (url === '/') return route.path === '/'
  return route.path.startsWith(url)
}
</script>

<template>
  <aside
    class="sidebar"
    :class="admin.sidebarCollapsed ? 'sidebar--collapsed' : 'sidebar--expanded'"
  >
    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="logo-icon gradient-primary">
        <Building2 :size="20" color="white" />
      </div>
      <span v-if="!admin.sidebarCollapsed" class="logo-text">Propify</span>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">
      <RouterLink
        v-for="item in menuItems"
        :key="item.url"
        :to="item.url"
        class="nav-item"
        :class="isActive(item.url) ? 'nav-item--active' : 'nav-item--default'"
        :title="admin.sidebarCollapsed ? item.title : ''"
      >
        <component
          :is="item.icon"
          :size="20"
          class="nav-icon flex-shrink-0 transition-colors"
          :class="isActive(item.url) ? 'icon--active' : 'icon--default'"
        />
        <span v-if="!admin.sidebarCollapsed">{{ item.title }}</span>
      </RouterLink>
    </nav>

    <!-- Collapse -->
    <button class="collapse-btn" @click="admin.toggleSidebar()">
      <ChevronLeft
        :size="16"
        class="transition-transform duration-300"
        :class="admin.sidebarCollapsed ? 'rotate-180' : ''"
      />
      <span v-if="!admin.sidebarCollapsed">Thu gọn</span>
    </button>
  </aside>
</template>

<style scoped>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  z-index: 40;
  height: 100vh;
  background-color: hsl(var(--card));
  border-right: 1px solid hsl(var(--border));
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  overflow: hidden;
}

.sidebar--expanded { width: 260px; }
.sidebar--collapsed { width: 72px; }

/* Logo */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 20px;
  height: 64px;
  border-bottom: 1px solid hsl(var(--border));
  flex-shrink: 0;
}

.logo-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-text {
  font-size: 18px;
  font-weight: 700;
  color: hsl(var(--foreground));
  letter-spacing: -0.01em;
  white-space: nowrap;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  transition: background-color 0.15s ease;
}

.nav-item--default {
  color: hsl(var(--sidebar-foreground));
}

.nav-item--default:hover {
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
}

.nav-item--active {
  background-color: hsl(var(--sidebar-accent));
  color: hsl(var(--sidebar-accent-foreground));
}

.icon--active {
  color: hsl(var(--primary));
}

.icon--default {
  color: hsl(var(--muted-foreground));
}

.nav-item--default:hover .icon--default {
  color: hsl(var(--foreground));
}

/* Collapse button */
.collapse-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 12px;
  padding: 8px 12px;
  border-radius: 8px;
  background: none;
  border: none;
  cursor: pointer;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.15s;
  white-space: nowrap;
}

.collapse-btn:hover {
  background-color: hsl(var(--muted));
}
</style>
