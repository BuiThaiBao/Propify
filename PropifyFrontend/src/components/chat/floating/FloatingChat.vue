<template>
  <Teleport to="body">
    <div v-if="authStore.isAuthenticated" class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-3 font-[Inter,-apple-system,sans-serif]">
      <ChatWindow :open="isOpen" :has-conversation="!!activeConversation">
        <ChatWindowHeader
          :conversation="activeConversation"
          :is-typing="isTyping"
          :unread-count="totalUnread"
          :show-search="showSearch"
          @back="closeConversation"
          @close="closeChat"
          @toggle-search="toggleSearch"
          @create-group="showCreateGroup = true"
          @show-group-info="showGroupInfo = true"
        />

        <UserSearchPanel
          v-if="!activeConversation"
          ref="searchPanel"
          :visible="showSearch"
          @start-chat="handleStartChat"
        />

        <ConversationList
          v-if="!activeConversation"
          :conversations="conversations"
          :loading="loadingConversations"
          compact
          @select="selectConversation"
        />

        <template v-else>
          <div ref="msgContainer" class="flex-1 overflow-y-auto px-3 pt-3 pb-1.5 flex flex-col gap-1 bg-[#f3f9ff] cw-msg-scroll" @scroll="onScroll">
            <button
              v-if="hasMore"
              class="self-center mb-2 bg-blue-50 text-blue-600 border border-blue-100 px-3.5 py-1 rounded-full text-[0.75rem] cursor-pointer transition-all hover:bg-blue-100 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loadingMessages"
              @click="loadMoreMessages"
            >
              {{ loadingMessages ? 'Đang tải...' : '↑ Tin nhắn cũ hơn' }}
            </button>

            <div v-if="loadingMessages && messages.length === 0" class="flex flex-col gap-2.5">
              <div
                v-for="i in 4"
                :key="i"
                class="h-[34px] rounded-[14px] max-w-[60%] animate-[cwPulse_1.4s_ease-in-out_infinite]"
                :class="i % 2 === 0 ? 'self-end bg-blue-100' : 'self-start bg-gray-200'"
              />
            </div>

            <MessageBubble
              v-for="msg in messages"
              :key="msg.id"
              :message="msg"
              :is-mine="msg.sender?.id === currentUserId"
              compact
            />
          </div>

          <ChatInput compact :disabled="sending" @send="sendMsg" @typing="onInputChange" />
        </template>
      </ChatWindow>

      <FloatingChatButton :is-open="isOpen" :unread-count="totalUnread" @toggle="toggleChat" />
      <CreateGroupModal v-model="showCreateGroup" @created="handleCreateGroup" />
      <GroupInfoPanel
        :visible="showGroupInfo"
        :conversation="activeConversation"
        :members="groupMembers"
        :loading-members="loadingMembers"
        :is-admin="isGroupAdmin"
        @close="showGroupInfo = false"
        @leave="handleLeaveGroup"
        @add-members="handleAddMembers"
        @remove-member="handleRemoveMember"
        @transfer-admin="handleTransferAdmin"
        @update-group="handleUpdateGroup"
      />
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/stores/auth';
import { useChatStore } from '@/stores/chat';
import ChatWindow from './ChatWindow.vue';
import ChatWindowHeader from './ChatWindowHeader.vue';
import FloatingChatButton from './FloatingChatButton.vue';
import ChatInput from '../shared/ChatInput.vue';
import ConversationList from '../shared/ConversationList.vue';
import MessageBubble from '../shared/MessageBubble.vue';
import UserSearchPanel from '../shared/UserSearchPanel.vue';
import CreateGroupModal from '../group/CreateGroupModal.vue';
import GroupInfoPanel from '../group/GroupInfoPanel.vue';

const authStore = useAuthStore();
const chatStore = useChatStore();

const {
  conversations,
  activeConversation,
  messages,
  loadingConversations,
  loadingMessages,
  sending,
  hasMore,
  typingUsers,
  totalUnread,
  isChatVisible,
  groupMembers,
  loadingMembers,
  isGroupAdmin,
} = storeToRefs(chatStore);

const {
  loadConversations,
  openConversation,
  loadMoreMessages,
  sendMessage,
  sendTypingIndicator,
  unsubscribeActive,
  openConversationWith,
  markAsRead,
  createGroup,
  addGroupMembers,
  removeGroupMember,
  transferGroupAdmin,
  leaveGroup,
  updateGroup,
} = chatStore;

const isOpen = ref(false);
const showSearch = ref(false);
const searchPanel = ref(null);
const msgContainer = ref(null);
const showCreateGroup = ref(false);
const showGroupInfo = ref(false);
const currentUserId = computed(() => authStore.user?.id);
const isTyping = computed(() => typingUsers.value.size > 0);
const isLoadingOlder = ref(false);

function toggleChat() {
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    if (conversations.value.length === 0) safeLoadConversations();
    if (activeConversation.value) {
      isChatVisible.value = true;
      markAsRead(activeConversation.value.id);
    }
    return;
  }

  isChatVisible.value = false;
  resetSearch();
}

function closeChat() {
  isOpen.value = false;
  isChatVisible.value = false;
  resetSearch();
}

function toggleSearch() {
  showSearch.value = !showSearch.value;
  if (!showSearch.value) {
    searchPanel.value?.reset?.();
  }
}

function resetSearch() {
  showSearch.value = false;
  searchPanel.value?.reset?.();
}

function closeConversation() {
  unsubscribeActive();
  isChatVisible.value = false;
}

async function selectConversation(conversation) {
  await openConversation(conversation);
  isChatVisible.value = true;
  scrollBottom(false);
}

async function handleStartChat(user) {
  showSearch.value = false;
  searchPanel.value?.reset?.();
  await openConversationWith(user.id);
  isChatVisible.value = true;
  safeLoadConversations(true);
  scrollBottom(false);
}

async function handleCreateGroup(payload) {
  await createGroup(payload.name, payload.memberIds);
  showCreateGroup.value = false;
  isOpen.value = true;
  isChatVisible.value = true;
  scrollBottom(false);
}

async function handleAddMembers(userIds) {
  await addGroupMembers(userIds);
}

async function handleRemoveMember(userId) {
  await removeGroupMember(userId);
}

async function handleTransferAdmin(userId) {
  await transferGroupAdmin(userId);
}

async function handleLeaveGroup() {
  await leaveGroup();
  showGroupInfo.value = false;
}

async function handleUpdateGroup(payload) {
  await updateGroup(payload);
}

async function sendMsg(text) {
  await sendMessage(text);
  scrollBottom();
}

let typingTimer = null;
function onInputChange() {
  if (typingTimer) return;
  sendTypingIndicator();
  typingTimer = setTimeout(() => {
    typingTimer = null;
  }, 2000);
}

async function onScroll() {
  if (msgContainer.value?.scrollTop === 0 && hasMore.value && !isLoadingOlder.value) {
    isLoadingOlder.value = true;
    const prevHeight = msgContainer.value.scrollHeight;
    await loadMoreMessages();
    nextTick(() => {
      if (msgContainer.value) {
        msgContainer.value.scrollTop = msgContainer.value.scrollHeight - prevHeight;
      }
      isLoadingOlder.value = false;
    });
  }
}

function scrollBottom(smooth = true) {
  nextTick(() => {
    if (msgContainer.value) {
      msgContainer.value.scrollTo({
        top: msgContainer.value.scrollHeight,
        behavior: smooth ? 'smooth' : 'instant',
      });
    }
  });
}

async function safeLoadConversations(force = false) {
  try {
    await loadConversations(force);
  } catch (err) {
    console.warn('[FloatingChat] Không load được conversations:', err?.message ?? err);
  }
}

watch(
  () => messages.value.length,
  (nextLength, prevLength) => {
    if (nextLength > prevLength && !isLoadingOlder.value) {
      scrollBottom();
    }
  },
);

watch(
  () => authStore.isAuthenticated,
  (auth) => {
    if (auth) safeLoadConversations();
  },
  { immediate: true },
);
</script>

<style scoped>
.cw-msg-scroll::-webkit-scrollbar { width: 4px; }
.cw-msg-scroll::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 99px; }

@keyframes cwPulse {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 0.9; }
}

@media (max-width: 480px) {
  .fixed.bottom-6.right-6 { bottom: 16px; right: 16px; }
}
</style>
