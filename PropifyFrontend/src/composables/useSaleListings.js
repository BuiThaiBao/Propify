import { computed, ref, watch } from 'vue';
import { keepPreviousData, useQuery, useQueryClient } from '@tanstack/vue-query';
import { useRoute } from 'vue-router';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';
import { listingKeys } from '@/composables/queryKeys';

async function fetchSalePage(page, keyword, posterType, minPrice, maxPrice, minArea, maxArea) {
  const response = await listingService.getPublicListings({
    demand_type: 'SALE',
    keyword: keyword?.trim() || undefined,
    per_page: 10,
    page,
    poster_type: posterType || undefined,
    min_price: minPrice !== null ? minPrice : undefined,
    max_price: maxPrice !== null ? maxPrice : undefined,
    min_area: minArea !== null ? minArea : undefined,
    max_area: maxArea !== null ? maxArea : undefined,
  });
  const data = response?.data?.data || [];
  await hydrateListingAddresses(data);
  return {
    data,
    meta: response?.data?.meta || {},
  };
}

export function useSaleListings() {
  const route = useRoute();
  const queryClient = useQueryClient();
  const enabled = ref(false);
  const currentPage = ref(1);
  const searchKeyword = ref(route?.query?.q || '');

  watch(
    () => route?.query?.q,
    (newVal) => {
      searchKeyword.value = newVal || '';
      currentPage.value = 1;
    }
  );

  // Filter states
  const posterType = ref(''); // '', 'OWNER', 'BROKER'
  const minPrice = ref(null);
  const maxPrice = ref(null);
  const minArea = ref(null);
  const maxArea = ref(null);

  const queryKey = computed(() => listingKeys.publicList({
    demand_type: 'SALE',
    keyword: searchKeyword.value.trim(),
    per_page: 10,
    page: currentPage.value,
    poster_type: posterType.value || undefined,
    min_price: minPrice.value !== null ? minPrice.value : undefined,
    max_price: maxPrice.value !== null ? maxPrice.value : undefined,
    min_area: minArea.value !== null ? minArea.value : undefined,
    max_area: maxArea.value !== null ? maxArea.value : undefined,
  }));

  const query = useQuery({
    queryKey,
    queryFn: () => fetchSalePage(
      currentPage.value,
      searchKeyword.value,
      posterType.value,
      minPrice.value,
      maxPrice.value,
      minArea.value,
      maxArea.value
    ),
    enabled,
    placeholderData: keepPreviousData,
    staleTime: 60 * 1000,
  });

  const saleListings = computed(() => query.data.value?.data || []);
  const saleTotal = computed(() => Number(query.data.value?.meta?.total || saleListings.value.length || 0));
  const lastPage = computed(() => Number(query.data.value?.meta?.last_page || 1));
  const saleLoading = computed(() => query.isLoading.value || query.isFetching.value);

  const saleSuggestions = computed(() => {
    const queryText = normalizeText(searchKeyword.value);
    if (!queryText) return [];

    const candidates = saleListings.value.flatMap((item) => [
      item.title,
      item.property?.full_address,
      item.property?.address_detail,
      item.property?.project_name,
    ]).filter(Boolean);

    return [...new Set(candidates)]
      .filter((text) => normalizeText(text).includes(queryText))
      .slice(0, 8);
  });

  const visiblePages = computed(() => {
    const total = lastPage.value;
    const current = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const pages = [1];
    if (current > 3) pages.push('...');

    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let i = start; i <= end; i += 1) pages.push(i);

    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
  });

  watch(
    () => [enabled.value, currentPage.value, searchKeyword.value, lastPage.value, posterType.value, minPrice.value, maxPrice.value, minArea.value, maxArea.value],
    ([isEnabled, page, keyword, totalPages, pType, minP, maxP, minA, maxA]) => {
      if (!isEnabled) return;
      prefetchNextPage(page, keyword, totalPages, pType, minP, maxP, minA, maxA);
    },
  );

  // Reset page when filters change
  watch(
    () => [posterType.value, minPrice.value, maxPrice.value, minArea.value, maxArea.value],
    () => {
      currentPage.value = 1;
    }
  );

  function init() {
    enabled.value = true;
  }

  async function fetchSaleListings() {
    enabled.value = true;
    return query.refetch();
  }

  async function onSearch(value) {
    searchKeyword.value = value || '';
    currentPage.value = 1;
  }

  async function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function prefetchNextPage(page, keyword, totalPages, pType, minP, maxP, minA, maxA) {
    const nextPage = Number(page) + 1;
    if (nextPage > Number(totalPages || 1)) return;

    queryClient.prefetchQuery({
      queryKey: listingKeys.publicList({
        demand_type: 'SALE',
        keyword: keyword?.trim(),
        per_page: 10,
        page: nextPage,
        poster_type: pType || undefined,
        min_price: minP !== null ? minP : undefined,
        max_price: maxP !== null ? maxP : undefined,
        min_area: minA !== null ? minA : undefined,
        max_area: maxA !== null ? maxA : undefined,
      }),
      queryFn: () => fetchSalePage(nextPage, keyword, pType, minP, maxP, minA, maxA),
      staleTime: 60 * 1000,
    });
  }

  return {
    saleListings,
    saleLoading,
    saleTotal,
    currentPage,
    lastPage,
    searchKeyword,
    saleSuggestions,
    visiblePages,
    posterType,
    minPrice,
    maxPrice,
    minArea,
    maxArea,
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
