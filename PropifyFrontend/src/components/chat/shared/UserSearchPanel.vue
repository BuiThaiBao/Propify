<template>
  <Transition name="search-slide">
    <div v-if="visible" class="px-3.5 py-3 border-b border-gray-100 bg-gray-50 shrink-0">
      <div class="relative">
        <div class="flex items-center bg-white border border-gray-200 rounded-xl px-3 py-1.5 gap-2 transition-all focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100">
          <svg class="shrink-0 text-gray-400" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <line x1="21" y1="21" x2="16.65" y2="16.65" />
          </svg>
          <input
            v-model="phone"
            type="tel"
            class="flex-1 bg-transparent border-none outline-none text-gray-800 text-[0.82rem] placeholder-gray-400"
            placeholder="Tìm theo số điện thoại..."
            autocomplete="off"
            @input="onInput"
          />
          <button v-if="phone" type="button" class="text-gray-400 hover:text-gray-600 bg-transparent border-none cursor-pointer p-0" @click="clear">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <div v-if="results.length > 0 || loading" class="mt-1.5 bg-white border border-gray-100 rounded-xl shadow-lg overflow-hidden">
          <div v-if="loading" class="flex items-center justify-center py-4 text-[0.8rem] text-gray-400">
            <svg class="animate-spin mr-2" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83" />
            </svg>
            Đang tìm...
          </div>
          <div
            v-for="user in results"
            :key="user.id"
            class="flex items-center gap-2.5 w-full px-3.5 py-2.5 border-b border-gray-50 last:border-b-0"
          >
            <div class="size-9 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-[0.72rem] font-bold text-white shrink-0">
              <img v-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" />
              <span v-else>{{ initials(user.full_name) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[0.84rem] font-semibold text-gray-800 truncate">{{ user.full_name }}</div>
              <div class="text-[0.72rem] text-gray-400">{{ user.phone }}</div>
            </div>
            <button
              class="text-[0.72rem] text-blue-500 font-medium shrink-0 flex items-center gap-1 bg-transparent border border-blue-200 rounded-lg px-2.5 py-1 cursor-pointer transition-all hover:bg-blue-50 disabled:opacity-50"
              :disabled="startingChat"
              @click="startChat(user)"
            >
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              Nhắn tin
            </button>
          </div>
        </div>

        <div
          v-else-if="phone.length >= 3 && !loading && results.length === 0 && error"
          class="mt-1.5 py-3 text-center text-[0.8rem] text-gray-400 bg-white border border-gray-100 rounded-xl"
        >
          Không tìm thấy người dùng
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
// 1. Imports
import { ref, computed } from 'vue';
import { useChatFormatters } from '@/composables/useChatFormatters';
import { useUserSearch } from '@/composables/useUserSearch';

// 2. Props & Emits
defineProps({
  visible: { type: Boolean, default: false },
});

const emit = defineEmits(['start-chat']);

// 3. State
const { initials } = useChatFormatters();
const phone = ref('');
const debouncedPhone = ref('');
const startingChat = ref(false);
let debounceTimer = null;

const { results, isLoading: loading, error: searchError } = useUserSearch(debouncedPhone);

// 4. Computed
const error = computed(() => {
  if (searchError.value) return 'fetch_error';
  if (debouncedPhone.value && !loading.value && results.value.length === 0) return 'empty';
  return '';
});

// 5. Watchers

// 6. Lifecycle

// 7. Functions
function onInput() {
  const digits = phone.value.replace(/\D/g, '');
  if (digits.length < 3) {
    debouncedPhone.value = '';
    return;
  }

  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    debouncedPhone.value = digits;
  }, 350);
}

function clear() {
  phone.value = '';
  debouncedPhone.value = '';
  clearTimeout(debounceTimer);
}

async function startChat(user) {
  startingChat.value = true;
  try {
    emit('start-chat', user);
  } finally {
    startingChat.value = false;
  }
}

defineExpose({
  reset: clear,
});
</script>

<style scoped>
.search-slide-enter-active { transition: max-height 0.25s ease, opacity 0.2s ease; }
.search-slide-leave-active { transition: max-height 0.2s ease, opacity 0.15s ease; }
.search-slide-enter-from,
.search-slide-leave-to { max-height: 0; opacity: 0; overflow: hidden; }
.search-slide-enter-to,
.search-slide-leave-from { max-height: 400px; opacity: 1; }
</style>
