<script setup>
import { computed } from 'vue'
import { Eye, Lock, Unlock } from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'

const props = defineProps({
  search: String,
  filterRole: String,
})

const users = [
  {
    id: 1,
    name: 'Nguyễn Văn A',
    joinDate: 'Tham gia: 10/01/2024',
    email: 'nguyenvana@email.com',
    phone: '0901234567',
    role: 'agent',
    roleLabel: 'Môi giới',
    posts: 25,
    status: 'active',
    statusLabel: 'Hoạt động',
    initial: 'N',
    avatarBg: '#eff6ff',
    avatarColor: '#2563eb',
  },
  {
    id: 2,
    name: 'Trần Thị B',
    joinDate: 'Tham gia: 20/02/2024',
    email: 'tranthib@email.com',
    phone: '0912345678',
    role: 'user',
    roleLabel: 'Người dùng',
    posts: 5,
    status: 'active',
    statusLabel: 'Hoạt động',
    initial: 'T',
    avatarBg: '#fef3c7',
    avatarColor: '#d97706',
  },
  {
    id: 3,
    name: 'Lê Văn C',
    joinDate: 'Tham gia: 01/03/2024',
    email: 'levanc@email.com',
    phone: '0923456789',
    role: 'agent',
    roleLabel: 'Môi giới',
    posts: 12,
    status: 'locked',
    statusLabel: 'Đã khóa',
    initial: 'L',
    avatarBg: '#d1fae5',
    avatarColor: '#059669',
  },
  {
    id: 4,
    name: 'Phạm Văn D',
    joinDate: 'Tham gia: 10/04/2024',
    email: 'phamvand@email.com',
    phone: '0934567890',
    role: 'user',
    roleLabel: 'Người dùng',
    posts: 3,
    status: 'active',
    statusLabel: 'Hoạt động',
    initial: 'P',
    avatarBg: '#ede9fe',
    avatarColor: '#7c3aed',
  },
  {
    id: 5,
    name: 'Hoàng Thị E',
    joinDate: 'Tham gia: 05/05/2024',
    email: 'hoangthie@email.com',
    phone: '0945678901',
    role: 'agent',
    roleLabel: 'Môi giới',
    posts: 45,
    status: 'active',
    statusLabel: 'Hoạt động',
    initial: 'H',
    avatarBg: '#fce7f3',
    avatarColor: '#db2777',
  },
  {
    id: 6,
    name: 'Đỗ Văn F',
    joinDate: 'Tham gia: 22/06/2024',
    email: 'dovanf@email.com',
    phone: '0956789012',
    role: 'user',
    roleLabel: 'Người dùng',
    posts: 0,
    status: 'locked',
    statusLabel: 'Đã khóa',
    initial: 'Đ',
    avatarBg: '#f1f5f9',
    avatarColor: '#475569',
  },
]

const filteredUsers = computed(() => {
  return users.filter(user => {
    const matchSearch = !props.search ||
      user.name.toLowerCase().includes(props.search.toLowerCase()) ||
      user.email.toLowerCase().includes(props.search.toLowerCase())
    const matchRole = props.filterRole === 'all' || user.role === props.filterRole
    return matchSearch && matchRole
  })
})

function roleVariant(role) {
  return role === 'agent' ? 'info' : 'default'
}

function statusVariant(status) {
  return status === 'active' ? 'success' : 'locked'
}
</script>

<template>
  <div class="users-table-wrap">
    <table class="users-table">
      <thead>
        <tr>
          <th>Người dùng</th>
          <th>Liên hệ</th>
          <th>Vai trò</th>
          <th>Tin đăng</th>
          <th>Trạng thái</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in filteredUsers" :key="user.id" class="user-row">
          <!-- User info -->
          <td>
            <div class="user-info">
              <div
                class="user-avatar"
                :style="{ backgroundColor: user.avatarBg, color: user.avatarColor }"
              >
                {{ user.initial }}
              </div>
              <div>
                <p class="user-name">{{ user.name }}</p>
                <p class="user-joindate">{{ user.joinDate }}</p>
              </div>
            </div>
          </td>

          <!-- Contact -->
          <td>
            <div class="user-contact">
              <p class="contact-email">✉ {{ user.email }}</p>
              <p class="contact-phone">📱 {{ user.phone }}</p>
            </div>
          </td>

          <!-- Role -->
          <td>
            <StatusBadge :text="user.roleLabel" :variant="roleVariant(user.role)" />
          </td>

          <!-- Posts count -->
          <td>
            <span class="user-posts">{{ user.posts }}</span>
          </td>

          <!-- Status -->
          <td>
            <StatusBadge :text="user.statusLabel" :variant="statusVariant(user.status)" />
          </td>

          <!-- Actions -->
          <td>
            <div class="user-actions">
              <button class="action-btn view" :id="`view-user-${user.id}`" title="Xem">
                <Eye :size="15" />
              </button>
              <button
                class="action-btn"
                :class="user.status === 'locked' ? 'unlock' : 'lock'"
                :id="`toggle-user-${user.id}`"
                :title="user.status === 'locked' ? 'Mở khóa' : 'Khóa'"
              >
                <component :is="user.status === 'locked' ? Unlock : Lock" :size="15" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="filteredUsers.length === 0" class="empty-state">
      <p>Không tìm thấy tài khoản phù hợp</p>
    </div>
  </div>
</template>

<style scoped>
.users-table-wrap {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.users-table thead tr {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.users-table th {
  padding: 12px 14px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.user-row {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.1s;
}

.user-row:last-child {
  border-bottom: none;
}

.user-row:hover {
  background-color: #f8fafc;
}

.users-table td {
  padding: 12px 14px;
  vertical-align: middle;
}

/* User info */
.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-name {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 2px 0;
}

.user-joindate {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
}

/* Contact */
.user-contact {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.contact-email {
  font-size: 12px;
  color: #2563eb;
  margin: 0;
}

.contact-phone {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.user-posts {
  font-weight: 600;
  color: #0f172a;
}

/* Actions */
.user-actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.action-btn.view {
  background-color: #f1f5f9;
  color: #64748b;
}

.action-btn.view:hover {
  background-color: #e2e8f0;
}

.action-btn.lock {
  background-color: #fee2e2;
  color: #dc2626;
}

.action-btn.lock:hover {
  background-color: #fecaca;
}

.action-btn.unlock {
  background-color: #d1fae5;
  color: #059669;
}

.action-btn.unlock:hover {
  background-color: #a7f3d0;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
}
</style>
