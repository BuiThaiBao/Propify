import { computed, ref, watch } from 'vue';
import { keepPreviousData, useQuery, useQueryClient } from '@tanstack/vue-query';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';
import { listingKeys } from '@/composables/queryKeys';

async function fetchSalePage(page, keyword) {
  const response = await listingService.getPublicListings({
    demand_type: 'SALE',
    keyword: keyword?.trim() || undefined,
    per_page: 10,
    page,
  });
  const data = response?.data?.data || [];
  await hydrateListingAddresses(data);
  return {
    data,
    meta: response?.data?.meta || {},
  };
}

export function useSaleListings() {
  const queryClient = useQueryClient();
  const enabled = ref(false);
  const currentPage = ref(1);
  const searchKeyword = ref('');

  const queryKey = computed(() => listingKeys.publicList({
    demand_type: 'SALE',
    keyword: searchKeyword.value.trim(),
    per_page: 10,
    page: currentPage.value,
  }));

  const query = useQuery({
    queryKey,
    queryFn: () => fetchSalePage(currentPage.value, searchKeyword.value),
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
    () => [enabled.value, currentPage.value, searchKeyword.value, lastPage.value],
    ([isEnabled, page, keyword, totalPages]) => {
      if (!isEnabled) return;
      prefetchNextPage(page, keyword, totalPages);
    },
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

  function prefetchNextPage(page, keyword, totalPages) {
    const nextPage = Number(page) + 1;
    if (nextPage > Number(totalPages || 1)) return;

    queryClient.prefetchQuery({
      queryKey: listingKeys.publicList({
        demand_type: 'SALE',
        keyword: keyword?.trim(),
        per_page: 10,
        page: nextPage,
      }),
      queryFn: () => fetchSalePage(nextPage, keyword),
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
