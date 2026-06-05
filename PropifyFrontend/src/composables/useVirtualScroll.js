import { computed, ref } from 'vue';

export function useVirtualScroll(items, options = {}) {
  const { itemHeight = 60, buffer = 5 } = options;

  const containerRef = ref(null);
  const scrollTop = ref(0);
  const containerHeight = ref(0);

  const startIndex = computed(() =>
    Math.max(0, Math.floor(scrollTop.value / itemHeight) - buffer),
  );

  const endIndex = computed(() =>
    Math.min(
      items.value.length,
      Math.ceil((scrollTop.value + containerHeight.value) / itemHeight) + buffer,
    ),
  );

  const visibleItems = computed(() => items.value.slice(startIndex.value, endIndex.value));
  const totalHeight = computed(() => items.value.length * itemHeight);
  const offsetY = computed(() => startIndex.value * itemHeight);

  function measureContainer() {
    if (containerRef.value) {
      containerHeight.value = containerRef.value.clientHeight;
      scrollTop.value = containerRef.value.scrollTop;
    }
  }

  function onScroll() {
    if (containerRef.value) {
      scrollTop.value = containerRef.value.scrollTop;
    }
  }

  return {
    containerRef,
    visibleItems,
    totalHeight,
    offsetY,
    onScroll,
    measureContainer,
    startIndex,
    endIndex,
  };
}
