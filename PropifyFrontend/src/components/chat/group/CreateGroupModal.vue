<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-[10000] bg-black/35 flex items-center justify-center p-4" @click.self="$emit('update:modelValue', false)">
      <div class="w-full max-w-[540px] bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h3 class="m-0 text-lg font-semibold text-gray-900">Tạo nhóm chat mới</h3>
          <button class="bg-transparent border-none text-gray-400 hover:text-gray-700 cursor-pointer" @click="$emit('update:modelValue', false)">✕</button>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên nhóm</label>
            <input v-model.trim="name" type="text" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Ví dụ: Nhóm môi giới Q7" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Thành viên</label>
            <div v-if="selectedUsers.length > 0" class="flex flex-wrap gap-2 mb-3">
              <span v-for="user in selectedUsers" :key="user.id" class="inline-flex items-center gap-1 rounded-full bg-blue-50 text-blue-700 px-3 py-1 text-xs font-medium">
                {{ user.full_name }}
                <button class="bg-transparent border-none text-blue-500 cursor-pointer p-0" @click="toggleUser(user)">✕</button>
              </span>
            </div>

            <input v-model.trim="query" type="text" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" placeholder="Tìm theo tên hoặc số điện thoại" @input="onSearch" />

            <div class="mt-3 max-h-[260px] overflow-y-auto rounded-xl border border-gray-100">
              <div v-if="loading" class="px-4 py-6 text-sm text-gray-400 text-center">Đang tìm...</div>
              <label
                v-for="user in results"
                :key="user.id"
                class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-b-0 cursor-pointer hover:bg-gray-50"
              >
                <input type="checkbox" :checked="selectedIds.has(user.id)" @change="toggleUser(user)" />
                <div class="size-9 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-[0.72rem] font-bold text-white shrink-0">
                  <img v-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" />
                  <span v-else>{{ initials(user.full_name) }}</span>
                </div>
                <div class="min-w-0">
                  <div class="text-sm font-medium text-gray-900 truncate">{{ user.full_name }}</div>
                  <div class="text-xs text-gray-400">{{ user.phone }}</div>
                </div>
              </label>
              <div v-if="!loading && results.length === 0" class="px-4 py-6 text-sm text-gray-400 text-center">Chưa có kết quả</div>
            </div>
          </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-100 flex justify-end gap-2">
          <button class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-gray-700 cursor-pointer" @click="$emit('update:modelValue', false)">Hủy</button>
          <button class="px-4 py-2 rounded-xl border-none bg-blue-600 text-white cursor-pointer disabled:opacity-50" :disabled="submitting || !canSubmit" @click="submit">
            {{ submitting ? 'Đang tạo...' : `Tạo nhóm (${selectedUsers.length})` }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
// 1. Imports
import { computed, ref, watch } from 'vue';
import { useChatFormatters } from '@/composables/useChatFormatters';
import { useUserSearch } from '@/composables/useUserSearch';

// 2. Props & Emits
const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'created']);

// 3. State & Composables
const { initials } = useChatFormatters();
const name = ref('');
const query = ref('');
const debouncedQuery = ref('');
const selectedUsers = ref([]);
const submitting = ref(false);
let debounceTimer = null;

const { results, isLoading: loading } = useUserSearch(debouncedQuery);

// 4. Computed
const selectedIds = computed(() => new Set(selectedUsers.value.map((user) => user.id)));
const canSubmit = computed(() => name.value.length >= 2 && selectedUsers.value.length > 0);

// 5. Watchers
watch(
  () => props.modelValue,
  (open) => {
    if (!open) reset();
  },
);

// 6. Lifecycle (none)

// 7. Functions
function reset() {
  name.value = '';
  query.value = '';
  debouncedQuery.value = '';
  selectedUsers.value = [];
  clearTimeout(debounceTimer);
}

function toggleUser(user) {
  const exists = selectedUsers.value.some((item) => item.id === user.id);
  if (exists) {
    selectedUsers.value = selectedUsers.value.filter((item) => item.id !== user.id);
    return;
  }

  selectedUsers.value = [...selectedUsers.value, user];
}

function onSearch() {
  clearTimeout(debounceTimer);
  const normalized = query.value.trim();
  if (normalized.length < 2) {
    debouncedQuery.value = '';
    return;
  }

  debounceTimer = setTimeout(() => {
    debouncedQuery.value = normalized;
  }, 300);
}

async function submit() {
  if (!canSubmit.value) return;
  submitting.value = true;
  try {
    emit('created', {
      name: name.value,
      memberIds: selectedUsers.value.map((user) => user.id),
    });
    emit('update:modelValue', false);
  } finally {
    submitting.value = false;
  }
}
</script>
