<script setup>
import { ref } from 'vue'
import { Eye, Lock, Unlock, Loader2 } from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'

const props = defineProps({
  users: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  actionError: { type: String, default: '' },
  updatingUserId: { type: [Number, String], default: null },
})

const emit = defineEmits(['change-status', 'view-detail'])

const isConfirmOpen = ref(false)
const confirmTitle = ref('')
const confirmDesc = ref('')
const selectedUser = ref(null)

function promptToggleStatus(user) {
  selectedUser.value = user
  if (user.status === 'locked') {
    confirmTitle.value = 'Mở khóa tài khoản'
    confirmDesc.value = `Bạn có chắc chắn muốn mở khóa tài khoản "${user.name}"? Người dùng này sẽ có thể đăng nhập lại vào hệ thống.`
  } else {
    confirmTitle.value = 'Khóa tài khoản'
    confirmDesc.value = `Bạn có chắc chắn muốn khóa tài khoản "${user.name}"? Người dùng này sẽ không thể đăng nhập hoặc thực hiện bất kỳ hành động nào.`
  }
  isConfirmOpen.value = true
}

function handleConfirm() {
  if (!selectedUser.value) return
  const newStatus = selectedUser.value.status === 'locked' ? 'active' : 'locked'
  emit('change-status', { id: selectedUser.value.id, status: newStatus })
  isConfirmOpen.value = false
}

function roleVariant(role) {
  return role === 'agent' ? 'info' : 'default'
}

function statusVariant(status) {
  return status === 'active' ? 'success' : 'locked'
}
</script>

<template>
  <div class="users-table-wrap">
    <!-- Action Error Alert -->
    <div v-if="actionError" class="action-error-alert">
      <span>{{ actionError }}</span>
    </div>

    <!-- Main Table / Loader -->
    <div v-if="loading && users.length === 0" class="state-container">
      <Loader2 :size="28" class="animate-spin text-blue-600" />
      <p class="state-text">Đang tải danh sách tài khoản...</p>
    </div>

    <div v-else-if="error" class="state-container error-state">
      <p class="state-text text-red-600">{{ error }}</p>
    </div>

    <table v-else class="users-table">
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
        <tr v-for="user in users" :key="user.id" class="user-row">
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
              <button
                class="action-btn view"
                :id="`view-user-${user.id}`"
                title="Xem"
                @click="emit('view-detail', user)"
              >
                <Eye :size="15" />
              </button>
              <button
                class="action-btn"
                :class="user.status === 'locked' ? 'unlock' : 'lock'"
                :id="`toggle-user-${user.id}`"
                :title="user.status === 'locked' ? 'Mở khóa' : 'Khóa'"
                :disabled="updatingUserId === user.id"
                @click="promptToggleStatus(user)"
              >
                <Loader2 v-if="updatingUserId === user.id" :size="15" class="animate-spin" />
                <component v-else :is="user.status === 'locked' ? Unlock : Lock" :size="15" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="!loading && users.length === 0 && !error" class="empty-state">
      <p>Không tìm thấy tài khoản phù hợp</p>
    </div>

    <!-- Confirm Modal -->
    <ConfirmModal
      :open="isConfirmOpen"
      :title="confirmTitle"
      :description="confirmDesc"
      :confirm-text="selectedUser?.status === 'locked' ? 'Mở khóa' : 'Khóa tài khoản'"
      :variant="selectedUser?.status === 'locked' ? 'default' : 'destructive'"
      @close="isConfirmOpen = false"
      @confirm="handleConfirm"
    />
  </div>
</template>

<style scoped>
.users-table-wrap {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.action-error-alert {
  padding: 12px 16px;
  background-color: #fef2f2;
  border-bottom: 1px solid #fee2e2;
  color: #b91c1c;
  font-size: 13px;
  font-weight: 500;
}

.state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  gap: 12px;
}

.state-text {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.error-state .state-text {
  color: #dc2626;
  font-weight: 500;
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

.action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.action-btn.view {
  background-color: #f1f5f9;
  color: #64748b;
}

.action-btn.view:hover:not(:disabled) {
  background-color: #e2e8f0;
}

.action-btn.lock {
  background-color: #fee2e2;
  color: #dc2626;
}

.action-btn.lock:hover:not(:disabled) {
  background-color: #fecaca;
}

.action-btn.unlock {
  background-color: #d1fae5;
  color: #059669;
}

.action-btn.unlock:hover:not(:disabled) {
  background-color: #a7f3d0;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
}

/* Animations */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.text-blue-600 {
  color: #2563eb;
}
.text-red-600 {
  color: #dc2626;
}
</style>
