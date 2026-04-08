<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAdminStore } from '@/stores/admin'
import {
  LayoutDashboard,
  FileText,
  Package,
  Users,
  Wrench,
  BarChart3,
  ChevronLeft,
  ChevronRight,
  Home,
} from 'lucide-vue-next'

const route = useRoute()
const adminStore = useAdminStore()

const navItems = [
  {
    name: 'Dashboard',
    path: '/',
    icon: LayoutDashboard,
    label: 'Dashboard',
  },
  {
    name: 'Posts',
    path: '/posts',
    icon: FileText,
    label: 'Quản lý tin đăng',
  },
  {
    name: 'Packages',
    path: '/packages',
    icon: Package,
    label: 'Quản lý gói tin',
  },
  {
    name: 'Users',
    path: '/users',
    icon: Users,
    label: 'Quản lý tài khoản',
  },
  {
    name: 'Utilities',
    path: '/utilities',
    icon: Wrench,
    label: 'Tiện ích hệ thống',
  },
  {
    name: 'Revenue',
    path: '/revenue',
    icon: BarChart3,
    label: 'Doanh thu & báo cáo',
  },
]

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>

<template>
  <aside class="sidebar" :class="{ collapsed: adminStore.sidebarCollapsed }">
    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="logo-icon">
        <Home :size="20" class="text-white" />
      </div>
      <span v-if="!adminStore.sidebarCollapsed" class="logo-text">Propify</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <RouterLink
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        class="nav-item"
        :class="{ active: isActive(item.path) }"
        :title="adminStore.sidebarCollapsed ? item.label : ''"
      >
        <component :is="item.icon" :size="18" class="nav-icon" />
        <span v-if="!adminStore.sidebarCollapsed" class="nav-label">{{ item.label }}</span>
      </RouterLink>
    </nav>

    <!-- Collapse Button -->
    <button class="collapse-btn" @click="adminStore.toggleSidebar()">
      <ChevronLeft v-if="!adminStore.sidebarCollapsed" :size="16" />
      <ChevronRight v-else :size="16" />
      <span v-if="!adminStore.sidebarCollapsed" class="collapse-text">Thu gọn</span>
    </button>
  </aside>
</template>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 240px;
  background-color: #ffffff;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  z-index: 50;
  transition: width 0.3s ease;
  overflow: hidden;
}

.sidebar.collapsed {
  width: 64px;
}

/* Logo */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  min-height: 64px;
}

.logo-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-text {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 12px 8px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 8px;
  text-decoration: none;
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.nav-item:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.nav-item.active {
  background-color: #eff6ff;
  color: #2563eb;
}

.nav-icon {
  flex-shrink: 0;
}

.nav-label {
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Collapse */
.collapse-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border: none;
  background: none;
  cursor: pointer;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 500;
  border-top: 1px solid #e2e8f0;
  width: 100%;
  text-align: left;
  white-space: nowrap;
  transition: color 0.15s ease;
}

.collapse-btn:hover {
  color: #64748b;
}

.collapse-text {
  overflow: hidden;
}
</style>
