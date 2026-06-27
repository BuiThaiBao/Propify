<template>
  <Teleport to="body">
    <Transition name="group-panel">
      <div v-if="visible" class="fixed inset-0 z-[10005] bg-black/25 flex justify-end" @click.self="$emit('close')">
        <div class="w-full max-w-[380px] h-full bg-white shadow-2xl flex flex-col">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
            <button class="bg-transparent border-none text-gray-500 cursor-pointer p-0 text-lg" @click="$emit('close')">←</button>
            <div class="text-base font-semibold text-gray-900 flex-1">Thông tin nhóm</div>
            <!-- Nút ... menu -->
            <div class="relative" ref="menuRef">
              <button
                class="size-8 rounded-xl border-none bg-transparent text-gray-500 cursor-pointer flex items-center justify-center hover:bg-gray-100 transition"
                @click.stop="menuOpen = !menuOpen"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="5" cy="12" r="2" />
                  <circle cx="12" cy="12" r="2" />
                  <circle cx="19" cy="12" r="2" />
                </svg>
              </button>
              <Transition name="menu-fade">
                <div
                  v-if="menuOpen"
                  class="absolute right-0 top-full mt-1 w-44 bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden z-50"
                >
                  <button
                    v-if="isAdmin"
                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-gray-700 bg-transparent border-none cursor-pointer text-left hover:bg-gray-50 transition"
                    @click="menuOpen = false; startEdit()"
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Chỉnh sửa
                  </button>
                  <button
                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-600 bg-transparent border-none cursor-pointer text-left hover:bg-red-50 transition"
                    @click="menuOpen = false; $emit('leave')"
                  >
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                      <polyline points="16 17 21 12 16 7" />
                      <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Rời nhóm
                  </button>
                </div>
              </Transition>
            </div>
          </div>

          <div class="px-5 py-5 border-b border-gray-100 text-center">
            <div class="flex justify-center mb-3">
              <GroupAvatar :avatar-url="draftAvatarUrl || conversation?.group?.avatar_url" :members="members" size="lg" />
            </div>
            <div v-if="editing" class="space-y-3 text-left">
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Tên nhóm</label>
                <input
                  v-model.trim="draftName"
                  type="text"
                  class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                  placeholder="Nhập tên nhóm"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Avatar URL</label>
                <input
                  v-model.trim="draftAvatarUrl"
                  type="url"
                  class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                  placeholder="https://..."
                />
              </div>
            </div>
            <div v-else class="text-lg font-semibold text-gray-900">{{ conversation?.group?.name ?? 'Nhóm chat' }}</div>
            <div class="text-sm text-gray-400 mt-1">{{ members.length }} thành viên</div>
            <div v-if="isAdmin" class="mt-3 flex justify-center gap-2">
              <template v-if="editing">
                <button class="rounded-xl border border-gray-200 bg-white text-gray-700 px-3 py-2 text-sm cursor-pointer" @click="cancelEdit">
                  Hủy
                </button>
                <button class="rounded-xl border-none bg-blue-600 text-white px-3 py-2 text-sm cursor-pointer disabled:opacity-50" :disabled="saving || !canSave" @click="saveEdit">
                  {{ saving ? 'Đang lưu...' : 'Lưu' }}
                </button>
              </template>
            </div>
          </div>

          <div class="flex-1 overflow-y-auto">
            <div class="px-5 py-4">
              <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-semibold text-gray-900">Thành viên</div>
                <button v-if="isAdmin" class="rounded-xl bg-blue-600 text-white border-none px-3 py-2 text-sm cursor-pointer" @click="showAddMember = true">
                  Thêm
                </button>
              </div>

              <div v-if="loadingMembers" class="text-sm text-gray-400 py-6 text-center">Đang tải thành viên...</div>
              <div v-else class="space-y-2">
                <div v-for="member in members" :key="member.id" class="flex items-center gap-3 rounded-xl border border-gray-100 px-3 py-3">
                  <div class="size-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-[0.72rem] font-bold text-white shrink-0">
                    <img v-if="member.avatar_url" :src="member.avatar_url" class="w-full h-full object-cover" />
                    <span v-else>{{ initials(member.full_name) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">{{ member.full_name }}</div>
                    <div class="text-xs text-gray-400">{{ member.role === 'admin' ? 'Quản trị viên' : 'Thành viên' }}</div>
                  </div>
                  <div v-if="isAdmin && member.id !== currentUserId" class="relative" @click.stop>
                    <button
                      class="size-7 rounded-lg border-none bg-transparent text-gray-400 cursor-pointer flex items-center justify-center hover:bg-gray-100 transition"
                      @click.stop="toggleMemberMenu(member.id)"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="5" cy="12" r="2" />
                        <circle cx="12" cy="12" r="2" />
                        <circle cx="19" cy="12" r="2" />
                      </svg>
                    </button>
                    <Transition name="menu-fade">
                      <div
                        v-if="openMemberMenu === member.id"
                        class="absolute right-0 top-full mt-1 w-44 bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden z-50"
                      >
                        <button
                          v-if="member.role !== 'admin'"
                          class="flex items-center gap-2.5 w-full px-3.5 py-2.5 text-sm text-gray-700 bg-transparent border-none cursor-pointer text-left hover:bg-gray-50 transition"
                          :disabled="transferringId === member.id"
                          @click="handleTransferAdmin(member.id); openMemberMenu = null"
                        >
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="8.5" cy="7" r="4" />
                            <polyline points="17 11 19 13 23 9" />
                          </svg>
                          Chuyển admin
                        </button>
                        <button
                          class="flex items-center gap-2.5 w-full px-3.5 py-2.5 text-sm text-red-600 bg-transparent border-none cursor-pointer text-left hover:bg-red-50 transition"
                          @click="openMemberMenu = null; $emit('remove-member', member.id)"
                        >
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                          </svg>
                          Xóa
                        </button>
                      </div>
                    </Transition>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <AddMemberModal
          v-model="showAddMember"
          :existing-member-ids="members.map((member) => member.id)"
          @submit="$emit('add-members', $event)"
        />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import AddMemberModal from './AddMemberModal.vue';
import GroupAvatar from './GroupAvatar.vue';
import { useAuthStore } from '@/stores/auth';
import { useChatFormatters } from '@/composables/useChatFormatters';

const props = defineProps({
  visible: { type: Boolean, default: false },
  conversation: { type: Object, default: null },
  members: { type: Array, default: () => [] },
  loadingMembers: { type: Boolean, default: false },
  isAdmin: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'leave', 'add-members', 'remove-member', 'transfer-admin', 'update-group']);

const authStore = useAuthStore();
const { initials } = useChatFormatters();
const currentUserId = authStore.user?.id;
const showAddMember = ref(false);
const editing = ref(false);
const saving = ref(false);
const transferringId = ref(null);
const draftName = ref('');
const draftAvatarUrl = ref('');
const menuOpen = ref(false);
const menuRef = ref(null);
const openMemberMenu = ref(null);

const canSave = computed(() => draftName.value.trim().length >= 2);

watch(
  () => [props.visible, props.conversation?.group?.name, props.conversation?.group?.avatar_url],
  () => {
    if (props.visible) {
      draftName.value = props.conversation?.group?.name ?? '';
      draftAvatarUrl.value = props.conversation?.group?.avatar_url ?? '';
    }
  },
  { immediate: true },
);

function startEdit() {
  editing.value = true;
  draftName.value = props.conversation?.group?.name ?? '';
  draftAvatarUrl.value = props.conversation?.group?.avatar_url ?? '';
}

function cancelEdit() {
  editing.value = false;
  saving.value = false;
  draftName.value = props.conversation?.group?.name ?? '';
  draftAvatarUrl.value = props.conversation?.group?.avatar_url ?? '';
}

async function saveEdit() {
  if (!canSave.value) return;
  saving.value = true;
  try {
    await emit('update-group', {
      name: draftName.value.trim(),
      avatar_url: draftAvatarUrl.value.trim() || null,
    });
    editing.value = false;
  } finally {
    saving.value = false;
  }
}

function toggleMemberMenu(id) {
  openMemberMenu.value = openMemberMenu.value === id ? null : id;
}

function onClickOutside(e) {
  if (menuRef.value && !menuRef.value.contains(e.target)) {
    menuOpen.value = false;
  }
  if (openMemberMenu.value) {
    openMemberMenu.value = null;
  }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));

async function handleTransferAdmin(memberId) {
  transferringId.value = memberId;
  try {
    await emit('transfer-admin', memberId);
  } finally {
    transferringId.value = null;
  }
}
</script>

<style scoped>
.group-panel-enter-active,
.group-panel-leave-active {
  transition: opacity 0.2s ease;
}

.group-panel-enter-from,
.group-panel-leave-to {
  opacity: 0;
}

.menu-fade-enter-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.menu-fade-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.menu-fade-enter-from,
.menu-fade-leave-to { opacity: 0; transform: translateY(-4px) scale(0.96); }
</style>
