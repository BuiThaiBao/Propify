<template>
  <img
    v-if="avatarUrl"
    :src="avatarUrl"
    alt="Avatar nhóm"
    class="rounded-full object-cover"
    :class="sizeClass"
  />

  <div v-else class="rounded-full overflow-hidden grid grid-cols-2 bg-blue-100" :class="sizeClass">
    <div
      v-for="(member, index) in displayMembers"
      :key="index"
      class="bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold"
      :style="{ fontSize }"
    >
      <img v-if="member?.avatar_url" :src="member.avatar_url" class="w-full h-full object-cover" />
      <span v-else>{{ initials(member?.full_name) }}</span>
    </div>
    <div
      v-for="index in emptySlots"
      :key="`empty-${index}`"
      class="bg-gradient-to-br from-blue-400 to-blue-600/80"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useChatFormatters } from '@/composables/useChatFormatters';

const props = defineProps({
  avatarUrl: { type: String, default: null },
  members: { type: Array, default: () => [] },
  size: { type: String, default: 'md' },
});

const { initials } = useChatFormatters();

const displayMembers = computed(() => props.members.slice(0, 4));
const emptySlots = computed(() => Math.max(0, 4 - displayMembers.value.length));
const sizeClass = computed(() => {
  if (props.size === 'sm') return 'size-8';
  if (props.size === 'lg') return 'size-14';
  return 'size-11';
});
const fontSize = computed(() => {
  if (props.size === 'sm') return '0.58rem';
  if (props.size === 'lg') return '0.9rem';
  return '0.7rem';
});
</script>
