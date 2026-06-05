<template>
  <Teleport to="body">
    <Transition name="group-panel">
      <div v-if="visible" class="fixed inset-0 z-[10005] bg-black/25 flex justify-end" @click.self="$emit('close')">
        <div class="w-full max-w-[380px] h-full bg-white shadow-2xl flex flex-col">
          <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
            <button class="bg-transparent border-none text-gray-500 cursor-pointer p-0" @click="$emit('close')">←</button>
            <div class="text-base font-semibold text-gray-900">Thông tin nhóm</div>
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
              <button
                v-if="!editing"
                class="rounded-xl border border-gray-200 bg-white text-gray-700 px-3 py-2 text-sm cursor-pointer"
                @click="startEdit"
              >
                Chỉnh sửa
              </button>
              <template v-else>
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
                  <div v-if="isAdmin && member.id !== currentUserId" class="flex items-center gap-2">
                    <button
                      v-if="member.role !== 'admin'"
                      class="text-xs rounded-lg border border-blue-200 bg-blue-50 text-blue-600 px-2.5 py-1 cursor-pointer disabled:opacity-50"
                      :disabled="transferringId === member.id"
                      @click="handleTransferAdmin(member.id)"
                    >
                      {{ transferringId === member.id ? 'Đang chuyển...' : 'Chuyển admin' }}
                    </button>
                    <button
                      class="text-xs rounded-lg border border-red-200 bg-red-50 text-red-600 px-2.5 py-1 cursor-pointer"
                      @click="$emit('remove-member', member.id)"
                    >
                      Xóa
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-gray-100">
            <button class="w-full rounded-xl border border-red-200 bg-red-50 text-red-600 px-4 py-3 cursor-pointer" @click="$emit('leave')">
              Rời nhóm
            </button>
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
import { computed, ref, watch } from 'vue';
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
</style>
