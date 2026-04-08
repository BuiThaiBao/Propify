<script setup>
import { ref } from 'vue'
import { Search, Bell, Plus, ChevronDown } from 'lucide-vue-next'

const searchValue = ref('')
</script>

<template>
  <header class="topbar">
    <!-- Search -->
    <div class="topbar-search">
      <Search :size="16" class="search-icon" />
      <input
        v-model="searchValue"
        type="text"
        placeholder="Tìm kiếm..."
        class="search-input"
        id="topbar-search"
      />
    </div>

    <!-- Right actions -->
    <div class="topbar-right">
      <!-- Tạo mới button -->
      <button class="btn-create gradient-primary gradient-primary-hover" id="btn-create-new">
        <Plus :size="16" />
        Tạo mới
      </button>

      <!-- Notifications -->
      <button class="notif-btn" id="btn-notifications">
        <Bell :size="20" class="text-muted-foreground" />
        <span class="notif-dot"></span>
      </button>

      <!-- Admin profile -->
      <div class="admin-profile" id="admin-profile">
        <div class="admin-avatar gradient-primary">A</div>
        <div class="admin-info">
          <p class="admin-name">Admin</p>
          <p class="admin-role">Quản trị viên</p>
        </div>
        <ChevronDown :size="16" class="text-muted-foreground" />
      </div>
    </div>
  </header>
</template>

<style scoped>
.topbar {
  height: 64px;
  background-color: hsl(var(--card));
  border-bottom: 1px solid hsl(var(--border));
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  position: sticky;
  top: 0;
  z-index: 30;
  flex-shrink: 0;
}

/* Search */
.topbar-search {
  position: relative;
  width: 320px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: hsl(var(--muted-foreground));
}

.search-input {
  width: 100%;
  padding: 8px 16px 8px 40px;
  background-color: hsl(var(--muted));
  border: none;
  border-radius: 8px;
  font-size: 14px;
  color: hsl(var(--foreground));
  outline: none;
  transition: box-shadow 0.15s;
}

.search-input::placeholder {
  color: hsl(var(--muted-foreground));
}

.search-input:focus {
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2);
}

/* Right */
.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-create {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  color: white;
  cursor: pointer;
  transition: box-shadow 0.15s, opacity 0.15s;
}

.btn-create:hover {
  box-shadow: 0 4px 12px hsl(217 91% 60% / 0.3);
}

.notif-btn {
  position: relative;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 8px;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}

.notif-btn:hover {
  background-color: hsl(var(--muted));
}

.notif-dot {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 8px;
  height: 8px;
  background-color: hsl(var(--destructive));
  border-radius: 50%;
}

.admin-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 12px;
  border-left: 1px solid hsl(var(--border));
  cursor: pointer;
  border-radius: 8px;
  transition: background-color 0.15s;
}

.admin-profile:hover {
  background-color: hsl(var(--muted));
}

.admin-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 0;
}

.admin-info {
  display: none;
}

@media (min-width: 768px) {
  .admin-info {
    display: block;
  }
}

.admin-name {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin: 0;
  line-height: 1.2;
}

.admin-role {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin: 2px 0 0 0;
  line-height: 1;
}
</style>
