import { computed, ref, watch } from 'vue';
import { keepPreviousData, useQuery, useQueryClient } from '@tanstack/vue-query';
import { useRoute, useRouter } from 'vue-router';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';
import { listingKeys } from '@/composables/queryKeys';

async function fetchRentPage(page, keyword, searchField, posterType, minPrice, maxPrice, minArea, maxArea, sortBy) {
  const response = await listingService.getPublicListings({
    demand_type: 'RENT',
    keyword: keyword?.trim() || undefined,
    search_field: searchField && searchField !== 'all' ? searchField : undefined,
    per_page: 10,
    page,
    poster_type: posterType || undefined,
    min_price: minPrice !== null ? minPrice : undefined,
    max_price: maxPrice !== null ? maxPrice : undefined,
    min_area: minArea !== null ? minArea : undefined,
    max_area: maxArea !== null ? maxArea : undefined,
    sort: sortBy || undefined,
  });
  const data = response?.data?.data || [];
  await hydrateListingAddresses(data);
  return {
    data,
    meta: response?.data?.meta || {},
  };
}

export function useRentListings() {
  const route = useRoute();
  const router = useRouter();
  const queryClient = useQueryClient();
  const enabled = ref(false);
  const currentPage = ref(1);
  const searchKeyword = ref(route?.query?.q || '');
  const searchField = ref(String(route?.query?.search_field || 'all'));

  watch(
    () => route?.query?.q,
    (newVal) => {
      searchKeyword.value = newVal || '';
      currentPage.value = 1;
    }
  );
  watch(
    () => route?.query?.sort,
    (newVal) => {
      const nextSort = String(newVal || '');
      if (nextSort !== sortBy.value) {
        sortBy.value = nextSort;
      }
    }
  );
  watch(
    () => route?.query?.search_field,
    (newVal) => {
      searchField.value = String(newVal || 'all');
      currentPage.value = 1;
    }
  );

  // Filter states
  const posterType = ref(''); // '', 'OWNER', 'BROKER'
  const minPrice = ref(null);
  const maxPrice = ref(null);
  const minArea = ref(null);
  const maxArea = ref(null);
  const sortBy = ref(String(route?.query?.sort || '')); // '', 'newest', 'oldest', 'price_asc', 'price_desc', 'area_asc', 'area_desc'

  const queryKey = computed(() => listingKeys.publicList({
    demand_type: 'RENT',
    keyword: searchKeyword.value.trim(),
    search_field: searchField.value !== 'all' ? searchField.value : undefined,
    per_page: 10,
    page: currentPage.value,
    poster_type: posterType.value || undefined,
    min_price: minPrice.value !== null ? minPrice.value : undefined,
    max_price: maxPrice.value !== null ? maxPrice.value : undefined,
    min_area: minArea.value !== null ? minArea.value : undefined,
    max_area: maxArea.value !== null ? maxArea.value : undefined,
    sort: sortBy.value || undefined,
  }));

  const query = useQuery({
    queryKey,
    queryFn: () => fetchRentPage(
      currentPage.value,
      searchKeyword.value,
      searchField.value,
      posterType.value,
      minPrice.value,
      maxPrice.value,
      minArea.value,
      maxArea.value,
      sortBy.value
    ),
    enabled,
    placeholderData: keepPreviousData,
    staleTime: 60 * 1000,
  });

  const rentListings = computed(() => query.data.value?.data || []);
  const rentTotal = computed(() => Number(query.data.value?.meta?.total || rentListings.value.length || 0));
  const lastPage = computed(() => Number(query.data.value?.meta?.last_page || 1));
  const rentLoading = computed(() => query.isLoading.value || query.isFetching.value);

  const rentSuggestions = computed(() => {
    const queryText = normalizeText(searchKeyword.value);
    if (!queryText) return [];

    const candidates = rentListings.value.flatMap((item) => [
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
    () => [enabled.value, currentPage.value, searchKeyword.value, searchField.value, lastPage.value, posterType.value, minPrice.value, maxPrice.value, minArea.value, maxArea.value, sortBy.value],
    ([isEnabled, page, keyword, selectedField, totalPages, pType, minP, maxP, minA, maxA, sBy]) => {
      if (!isEnabled) return;
      prefetchNextPage(page, keyword, selectedField, totalPages, pType, minP, maxP, minA, maxA, sBy);
    },
  );

  // Reset page when filters change
  watch(
    () => [posterType.value, minPrice.value, maxPrice.value, minArea.value, maxArea.value, sortBy.value, searchField.value],
    () => {
      currentPage.value = 1;
    }
  );
  watch(searchField, (nextField) => {
    const currentField = String(route?.query?.search_field || 'all');
    const normalizedNextField = String(nextField || 'all');
    const currentKeyword = String(route?.query?.q || '');

    searchKeyword.value = '';
    currentPage.value = 1;

    if (currentField === normalizedNextField && currentKeyword === '') return;

    router.replace({
      query: {
        ...route.query,
        q: undefined,
        search_field: normalizedNextField !== 'all' ? normalizedNextField : undefined,
      },
    }).catch(() => {});
  });
  watch(sortBy, (nextSort) => {
    const currentSort = String(route?.query?.sort || '');
    const normalizedNextSort = String(nextSort || '');
    if (currentSort === normalizedNextSort) return;

    router.replace({
      query: {
        ...route.query,
        sort: normalizedNextSort || undefined,
      },
    }).catch(() => {});
  });

  function init() {
    enabled.value = true;
  }

  async function fetchRentListings() {
    enabled.value = true;
    return query.refetch();
  }

  async function onSearch(value) {
    searchKeyword.value = value || '';
    currentPage.value = 1;
    const normalizedNextQuery = searchKeyword.value.trim();
    const currentQuery = String(route?.query?.q || '');
    if (currentQuery !== normalizedNextQuery) {
      const nextQuery = { ...route.query };
      if (normalizedNextQuery) {
        nextQuery.q = normalizedNextQuery;
      } else {
        delete nextQuery.q;
      }
      nextQuery.search_field = searchField.value !== 'all' ? searchField.value : undefined;

      router.replace({
        query: nextQuery,
      }).catch(() => {});
    }
  }

  async function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function prefetchNextPage(page, keyword, selectedField, totalPages, pType, minP, maxP, minA, maxA, sBy) {
    const nextPage = Number(page) + 1;
    if (nextPage > Number(totalPages || 1)) return;

    queryClient.prefetchQuery({
      queryKey: listingKeys.publicList({
        demand_type: 'RENT',
        keyword: keyword?.trim(),
        search_field: selectedField && selectedField !== 'all' ? selectedField : undefined,
        per_page: 10,
        page: nextPage,
        poster_type: pType || undefined,
        min_price: minP !== null ? minP : undefined,
        max_price: maxP !== null ? maxP : undefined,
        min_area: minA !== null ? minA : undefined,
        max_area: maxA !== null ? maxA : undefined,
        sort: sBy || undefined,
      }),
      queryFn: () => fetchRentPage(nextPage, keyword, selectedField, pType, minP, maxP, minA, maxA, sBy),
      staleTime: 60 * 1000,
    });
  }

  return {
    rentListings,
    rentLoading,
    rentTotal,
    currentPage,
    lastPage,
    searchKeyword,
    searchField,
    rentSuggestions,
    visiblePages,
    posterType,
    minPrice,
    maxPrice,
    minArea,
    maxArea,
    sortBy,
    init,
    fetchRentListings,
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
