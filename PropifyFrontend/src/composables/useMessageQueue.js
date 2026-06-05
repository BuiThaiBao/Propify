import { ref } from 'vue';

export function useMessageQueue() {
  const queue = ref([]);
  const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine);

  function enqueue(conversationId, body, meta = {}) {
    const item = {
      id: `q-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
      conversationId,
      body,
      status: 'queued',
      retries: 0,
      maxRetries: 3,
      createdAt: new Date().toISOString(),
      ...meta,
    };

    queue.value.push(item);
    return item;
  }

  async function flush(sendFn) {
    const pending = queue.value.filter((item) => item.status === 'queued' || item.status === 'failed');

    for (const item of pending) {
      if (item.retries >= item.maxRetries) {
        item.status = 'permanently_failed';
        continue;
      }

      try {
        item.status = 'sending';
        item.retries += 1;
        await sendFn(item);
        item.status = 'sent';
      } catch {
        item.status = 'failed';
      }
    }

    queue.value = queue.value.filter((item) => item.status !== 'sent');
  }

  function remove(id) {
    queue.value = queue.value.filter((item) => item.id !== id);
  }

  function setupListeners(sendFn) {
    if (typeof window === 'undefined') return () => {};

    const handleOnline = () => {
      isOnline.value = true;
      flush(sendFn);
    };
    const handleOffline = () => {
      isOnline.value = false;
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }

  return { queue, isOnline, enqueue, flush, remove, setupListeners };
}
