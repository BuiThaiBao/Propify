/**
 * Composable: useSaleListings
 * 
 * Cache + state sống ở MODULE LEVEL (bên ngoài function).
 * → Persist qua navigation (component unmount/remount).
 * → Khi user vào detail rồi quay lại, data hiển thị ngay lập tức.
 */
import { ref, computed } from 'vue';
import listingService from '@/services/listingService';

// ==================== MODULE-LEVEL STATE (persist qua navigation) ====================

/** Page cache: Map<"keyword::page", { data, meta }> */
const pageCache = new Map();

/** Reactive state — giữ nguyên giá trị khi component remount */
const saleListings = ref([]);
const saleLoading = ref(false);
const saleTotal = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const searchKeyword = ref('');

/** Đánh dấu đã load lần đầu chưa */
let initialized = false;

// ==================== Internal helpers ====================

function cacheKey(page, keyword) {
  return `${(keyword || '').trim()}::${page}`;
}

async function fetchPageData(page, keyword) {
  const response = await listingService.getPublicListings({
    demand_type: 'SALE',
    keyword: keyword?.trim() || undefined,
    per_page: 10,
    page,
  });
  const data = response?.data?.data || [];
  const meta = response?.data?.meta || {};
  return { data, meta };
}

function applyPageData({ data, meta }) {
  saleListings.value = data;
  saleTotal.value = Number(meta.total || data.length || 0);
  currentPage.value = Number(meta.current_page || 1);
  lastPage.value = Number(meta.last_page || 1);
  saleLoading.value = false;
}

function prefetchNextPage(currentPg, keyword) {
  const nextPg = currentPg + 1;
  if (nextPg > lastPage.value) return;

  const key = cacheKey(nextPg, keyword);
  if (pageCache.has(key)) return;

  fetchPageData(nextPg, keyword)
    .then((result) => {
      pageCache.set(key, result);
    })
    .catch(() => {
      // Bỏ qua lỗi prefetch
    });
}

// ==================== Public composable ====================

export function useSaleListings() {

  const saleSuggestions = computed(() => {
    const query = normalizeText(searchKeyword.value);
    if (!query) return [];

    const candidates = saleListings.value.flatMap((item) => [
      item.title,
      item.property?.address_detail,
      item.property?.project_name,
    ]).filter(Boolean);

    return [...new Set(candidates)]
      .filter((text) => normalizeText(text).includes(query))
      .slice(0, 8);
  });

  const visiblePages = computed(() => {
    const total = lastPage.value;
    const current = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const pages = [];
    pages.push(1);
    if (current > 3) pages.push('...');

    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);

    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
  });

  /**
   * Fetch trang hiện tại: cache hit → instant, cache miss → API call.
   */
  async function fetchSaleListings() {
    const page = currentPage.value;
    const keyword = searchKeyword.value;
    const key = cacheKey(page, keyword);

    // Cache HIT → instant
    if (pageCache.has(key)) {
      applyPageData(pageCache.get(key));
      prefetchNextPage(page, keyword);
      return;
    }

    // Cache MISS → API
    saleLoading.value = true;
    try {
      const result = await fetchPageData(page, keyword);
      pageCache.set(key, result);
      applyPageData(result);
      prefetchNextPage(page, keyword);
    } catch (error) {
      console.error('Failed to fetch sale listings', error);
      saleListings.value = [];
      saleTotal.value = 0;
    } finally {
      saleLoading.value = false;
    }
  }

  async function onSearch(value) {
    searchKeyword.value = value || '';
    currentPage.value = 1;
    pageCache.clear();
    await fetchSaleListings();
  }

  async function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    currentPage.value = page;
    await fetchSaleListings();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /**
   * Gọi trong onMounted — nếu đã có data (quay lại từ detail) thì skip fetch.
   */
  async function init() {
    if (initialized && saleListings.value.length > 0) {
      // Đã có data → không cần fetch lại, hiển thị ngay
      return;
    }
    initialized = true;
    await fetchSaleListings();
  }

  return {
    // State
    saleListings,
    saleLoading,
    saleTotal,
    currentPage,
    lastPage,
    searchKeyword,
    // Computed
    saleSuggestions,
    visiblePages,
    // Actions
    init,
    fetchSaleListings,
    onSearch,
    goToPage,
  };
}

function normalizeText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}
