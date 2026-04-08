<script setup>
import { ref } from 'vue'
import { Search, Plus, Bell, ChevronDown, User } from 'lucide-vue-next'

const searchQuery = ref('')
const showNotifications = ref(false)
</script>

<template>
  <header class="topbar">
    <!-- Search -->
    <div class="topbar-search">
      <Search :size="16" class="search-icon" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Tìm kiếm..."
        class="search-input"
      />
    </div>

    <!-- Actions -->
    <div class="topbar-actions">
      <!-- Create button -->
      <button class="btn-create">
        <Plus :size="16" />
        <span>Tạo mới</span>
      </button>

      <!-- Notifications -->
      <button
        class="icon-btn"
        @click="showNotifications = !showNotifications"
        id="notif-btn"
      >
        <Bell :size="20" />
        <span class="notif-dot"></span>
      </button>

      <!-- Admin profile -->
      <button class="admin-profile" id="admin-profile-btn">
        <div class="admin-avatar">A</div>
        <div class="admin-info">
          <span class="admin-name">Admin</span>
          <span class="admin-role">Quản trị viên</span>
        </div>
        <ChevronDown :size="14" class="text-slate-400" />
      </button>
    </div>
  </header>
</template>

<style scoped>
.topbar {
  height: 64px;
  background-color: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 40;
}

/* Search */
.topbar-search {
  flex: 1;
  max-width: 400px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input {
  width: 100%;
  height: 38px;
  padding: 0 12px 0 36px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #f8fafc;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  background-color: #fff;
}

/* Actions */
.topbar-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: auto;
}

.btn-create {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.15s;
}

.btn-create:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn-create:active {
  transform: translateY(0);
}

.icon-btn {
  width: 38px;
  height: 38px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  position: relative;
}

.icon-btn:hover {
  background-color: #f8fafc;
  color: #0f172a;
}

.notif-dot {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 8px;
  height: 8px;
  background-color: #ef4444;
  border-radius: 50%;
  border: 2px solid white;
}

/* Admin profile */
.admin-profile {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: background-color 0.15s;
}

.admin-profile:hover {
  background-color: #f8fafc;
}

.admin-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.admin-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.admin-name {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.2;
}

.admin-role {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.2;
}
</style>
