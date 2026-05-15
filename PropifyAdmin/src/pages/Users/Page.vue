<script setup>
import { ref, computed } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Search, Filter, Eye, Lock, Unlock, Mail, Phone } from 'lucide-vue-next'

const search = ref('')
const roleFilter = ref('all')
const confirmModal = ref({ open: false, title: '', desc: '' })

const mockUsers = [
  { id: 1, name: 'Nguyễn Văn A', email: 'nguyenvana@email.com', phone: '0901234567', role: 'agent', status: 'approved', posts: 25, joinDate: '15/01/2024' },
  { id: 2, name: 'Trần Thị B', email: 'tranthib@email.com', phone: '0912345678', role: 'user', status: 'approved', posts: 5, joinDate: '20/02/2024' },
  { id: 3, name: 'Lê Văn C', email: 'levanc@email.com', phone: '0923456789', role: 'agent', status: 'locked', posts: 12, joinDate: '01/03/2024' },
  { id: 4, name: 'Phạm Văn D', email: 'phamvand@email.com', phone: '0934567890', role: 'user', status: 'approved', posts: 3, joinDate: '10/04/2024' },
  { id: 5, name: 'Hoàng Thị E', email: 'hoangthie@email.com', phone: '0945678901', role: 'agent', status: 'approved', posts: 45, joinDate: '05/05/2024' },
  { id: 6, name: 'Đỗ Văn F', email: 'dovanf@email.com', phone: '0956789012', role: 'user', status: 'locked', posts: 0, joinDate: '22/06/2024' },
]

const filtered = computed(() => {
  return mockUsers.filter(u => {
    if (search.value && !u.name.toLowerCase().includes(search.value.toLowerCase()) && !u.email.toLowerCase().includes(search.value.toLowerCase())) return false
    if (roleFilter.value !== 'all' && u.role !== roleFilter.value) return false
    return true
  })
})
</script>

<template>
  <div>
    <PageHeader title="Quản lý tài khoản" description="Quản lý người dùng và môi giới trên hệ thống" />

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <Search :size="16" class="search-icon" />
        <input
          v-model="search"
          type="text"
          placeholder="Tìm kiếm tài khoản..."
          class="filter-input"
          id="users-search"
        />
      </div>
      <div class="filter-right">
        <Filter :size="16" color="hsl(215,16%,47%)" />
        <select v-model="roleFilter" class="filter-select" id="users-role-filter">
          <option value="all">Tất cả vai trò</option>
          <option value="user">Người dùng</option>
          <option value="agent">Môi giới</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr class="table-head-row">
              <th class="th">Người dùng</th>
              <th class="th">Liên hệ</th>
              <th class="th">Vai trò</th>
              <th class="th">Tin đăng</th>
              <th class="th">Trạng thái</th>
              <th class="th th-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filtered" :key="user.id" class="table-row">
              <!-- User info -->
              <td class="td">
                <div class="user-info">
                  <div class="user-avatar gradient-primary">{{ user.name.charAt(0) }}</div>
                  <div>
                    <p class="user-name">{{ user.name }}</p>
                    <p class="user-join">Tham gia: {{ user.joinDate }}</p>
                  </div>
                </div>
              </td>
              <!-- Contact -->
              <td class="td">
                <div class="contact">
                  <p class="contact-line"><Mail :size="14" color="hsl(215,16%,47%)" /> {{ user.email }}</p>
                  <p class="contact-line"><Phone :size="14" color="hsl(215,16%,47%)" /> {{ user.phone }}</p>
                </div>
              </td>
              <!-- Role -->
              <td class="td">
                <span class="role-badge" :class="user.role === 'agent' ? 'role-agent' : 'role-user'">
                  {{ user.role === 'agent' ? 'Môi giới' : 'Người dùng' }}
                </span>
              </td>
              <!-- Posts -->
              <td class="td user-posts">{{ user.posts }}</td>
              <!-- Status -->
              <td class="td">
                <StatusBadge
                  :status="user.status === 'locked' ? 'locked' : 'approved'"
                  :label="user.status === 'locked' ? 'Đã khóa' : 'Hoạt động'"
                />
              </td>
              <!-- Actions -->
              <td class="td">
                <div class="actions">
                  <button class="act-btn" :id="`view-user-${user.id}`" title="Xem chi tiết">
                    <Eye :size="16" color="hsl(215,16%,47%)" />
                  </button>
                  <button
                    class="act-btn"
                    :id="`toggle-user-${user.id}`"
                    :title="user.status === 'locked' ? 'Mở khóa' : 'Khóa'"
                    @click="confirmModal = { open: true, title: user.status === 'locked' ? 'Mở khóa tài khoản' : 'Khóa tài khoản', desc: `Bạn có muốn ${user.status === 'locked' ? 'mở khóa' : 'khóa'} tài khoản &quot;${user.name}&quot;?` }"
                  >
                    <component
                      :is="user.status === 'locked' ? Unlock : Lock"
                      :size="16"
                      :color="user.status === 'locked' ? 'hsl(var(--success))' : 'hsl(var(--destructive))'"
                    />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :description="confirmModal.desc"
      @close="confirmModal.open = false"
      @confirm="confirmModal.open = false"
    />
  </div>
</template>

<style scoped>
.filter-bar {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 16px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  margin-bottom: 24px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
}

.filter-search {
  flex: 1;
  min-width: 240px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: hsl(var(--muted-foreground));
}

.filter-input {
  width: 100%;
  padding: 8px 16px 8px 40px;
  background-color: hsl(var(--muted));
  border: none;
  border-radius: 8px;
  font-size: 14px;
  color: hsl(var(--foreground));
  outline: none;
}
.filter-input:focus { box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2); }

.filter-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-select {
  background-color: hsl(var(--muted));
  font-size: 14px;
  border-radius: 8px;
  padding: 8px 12px;
  border: none;
  color: hsl(var(--foreground));
  outline: none;
  cursor: pointer;
}
.filter-select:focus { box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2); }

.table-wrap {
  background-color: hsl(var(--card));
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  overflow: hidden;
}

.table-scroll { overflow-x: auto; }

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.table-head-row {
  border-bottom: 1px solid hsl(var(--border));
  background-color: hsl(var(--muted) / 0.5);
}

.th {
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 20px;
}

.th-right { text-align: right; }

.table-row {
  border-bottom: 1px solid hsl(var(--border));
  transition: background-color 0.1s;
}
.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: hsl(var(--muted) / 0.3); }

.td { padding: 16px 20px; vertical-align: middle; }

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 0;
}

.user-name {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin: 0 0 2px;
}

.user-join {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

.contact {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.contact-line {
  font-size: 14px;
  color: hsl(var(--foreground));
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
}

.role-agent {
  background-color: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.role-user {
  background-color: hsl(var(--muted));
  color: hsl(var(--muted-foreground));
}

.user-posts {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--foreground));
}

.actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}

.act-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}
.act-btn:hover { background-color: hsl(var(--muted)); }
</style>
