import { computed, ref, watch } from "vue";
import {
  keepPreviousData,
  useQuery,
  useQueryClient,
} from "@tanstack/vue-query";
import { useRoute, useRouter } from "vue-router";
import listingService from "@/services/listingService";
import { listingKeys } from "@/composables/queryKeys";
import { hydrateListingAddresses } from "@/utils/addressFormatter";

function normalizeText(value) {
  return String(value || "")
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase();
}

async function fetchPublicListingsPage(options) {
  const response = await listingService.getPublicListings({
    demand_type: options.demandType,
    keyword: options.keyword?.trim() || undefined,
    search_field:
      options.searchField && options.searchField !== "all"
        ? options.searchField
        : undefined,
    per_page: options.pageSize,
    page: options.page,
    poster_type: options.posterType || undefined,
    min_price: options.minPrice !== null ? options.minPrice : undefined,
    max_price: options.maxPrice !== null ? options.maxPrice : undefined,
    min_area: options.minArea !== null ? options.minArea : undefined,
    max_area: options.maxArea !== null ? options.maxArea : undefined,
    sort: options.sortBy || undefined,
    property_type: options.propertyType || undefined,
  });

  const data = response?.data?.data || [];
  await hydrateListingAddresses(data);

  return {
    data,
    meta: response?.data?.meta || {},
  };
}

export function usePublicListings(options = {}) {
  const { demandType, pageSize = 10 } = options;

  const route = useRoute();
  const router = useRouter();
  const queryClient = useQueryClient();

  const enabled = ref(false);
  const searchKeyword = ref(route?.query?.q || "");
  const searchField = ref(String(route?.query?.search_field || "all"));
  const posterType = ref("");
  const minPrice = ref(null);
  const maxPrice = ref(null);
  const minArea = ref(null);
  const maxArea = ref(null);
  const sortBy = ref(String(route?.query?.sort || ""));
  const propertyType = ref(route?.query?.property_type || "");

  // Infinite scroll state
  const allListings = ref([]);          // accumulated across pages
  const loadedPages = ref(0);            // how many pages have been fetched
  const hasMore = ref(true);
  const loadingError = ref(null);         // error message from last fetch
  const isLoadingMore = ref(false);       // true while fetching next page
  const isInitialLoading = ref(false);    // true only for very first fetch

  watch(
    () => route?.query?.property_type,
    (newVal) => {
      propertyType.value = newVal || "";
      resetInfiniteScroll();
    },
  );

  watch(
    () => route?.query?.q,
    (newVal) => {
      searchKeyword.value = newVal || "";
      resetInfiniteScroll();
    },
  );

  watch(
    () => route?.query?.sort,
    (newVal) => {
      const nextSort = String(newVal || "");
      if (nextSort !== sortBy.value) {
        sortBy.value = nextSort;
      }
    },
  );

  watch(
    () => route?.query?.search_field,
    (newVal) => {
      searchField.value = String(newVal || "all");
      resetInfiniteScroll();
    },
  );

  const createPayload = (page) => ({
    demand_type: demandType,
    keyword: searchKeyword.value.trim(),
    search_field: searchField.value !== "all" ? searchField.value : undefined,
    per_page: pageSize,
    page,
    poster_type: posterType.value || undefined,
    min_price: minPrice.value !== null ? minPrice.value : undefined,
    max_price: maxPrice.value !== null ? maxPrice.value : undefined,
    min_area: minArea.value !== null ? minArea.value : undefined,
    max_area: maxArea.value !== null ? maxArea.value : undefined,
    sort: sortBy.value || undefined,
    property_type: propertyType.value || undefined,
  });

  // single page query (kept for cache / prefetch infra)
  const currentPageForQuery = ref(1);
  const queryKey = computed(() =>
    listingKeys.publicList(createPayload(currentPageForQuery.value)),
  );

  const query = useQuery({
    queryKey,
    queryFn: () =>
      fetchPublicListingsPage({
        demandType,
        pageSize,
        page: currentPageForQuery.value,
        keyword: searchKeyword.value,
        searchField: searchField.value,
        posterType: posterType.value,
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        minArea: minArea.value,
        maxArea: maxArea.value,
        sortBy: sortBy.value,
        propertyType: propertyType.value,
      }),
    enabled,
    placeholderData: keepPreviousData,
    staleTime: 60 * 1000,
  });

  const listings = computed(() => allListings.value);
  const total = computed(() =>
    Number(query.data.value?.meta?.total || allListings.value.length || 0),
  );
  const lastPage = computed(() =>
    Number(query.data.value?.meta?.last_page || 1),
  );
  const isLoading = computed(
    () => query.isLoading.value || query.isFetching.value,
  );

  // --- Infinite scroll: load next page ---
  async function loadMore() {
    if (isLoadingMore.value || !hasMore.value) return;
    loadingError.value = null;
    isLoadingMore.value = true;

    const nextPage = loadedPages.value + 1;

    try {
      const result = await fetchPublicListingsPage({
        demandType,
        pageSize,
        page: nextPage,
        keyword: searchKeyword.value,
        searchField: searchField.value,
        posterType: posterType.value,
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        minArea: minArea.value,
        maxArea: maxArea.value,
        sortBy: sortBy.value,
        propertyType: propertyType.value,
      });

      allListings.value = [...allListings.value, ...result.data];
      loadedPages.value = nextPage;
      hasMore.value = nextPage < (result.meta?.last_page || 1);
    } catch (err) {
      loadingError.value =
        "Không thể tải thêm dữ liệu. Vui lòng kiểm tra kết nối.";
    } finally {
      isLoadingMore.value = false;
    }
  }

  // Load first page when init() called
  async function init() {
    enabled.value = true;
    isInitialLoading.value = true;
    loadingError.value = null;

    try {
      const result = await fetchPublicListingsPage({
        demandType,
        pageSize,
        page: 1,
        keyword: searchKeyword.value,
        searchField: searchField.value,
        posterType: posterType.value,
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        minArea: minArea.value,
        maxArea: maxArea.value,
        sortBy: sortBy.value,
        propertyType: propertyType.value,
      });

      allListings.value = result.data;
      loadedPages.value = 1;
      hasMore.value = 1 < (result.meta?.last_page || 1);
      currentPageForQuery.value = 1;
    } catch (err) {
      loadingError.value =
        "Không thể tải dữ liệu. Vui lòng kiểm tra kết nối.";
    } finally {
      isInitialLoading.value = false;
    }
  }

  function resetInfiniteScroll() {
    allListings.value = [];
    loadedPages.value = 0;
    hasMore.value = true;
    loadingError.value = null;
    isLoadingMore.value = false;
    if (enabled.value) init();
  }

  function refetchListings() {
    return init();
  }

  function onSearch(value) {
    searchKeyword.value = value || "";
    const normalizedNextQuery = searchKeyword.value.trim();
    const currentQuery = String(route?.query?.q || "");
    if (currentQuery === normalizedNextQuery) return;

    const nextQuery = { ...route.query };
    if (normalizedNextQuery) {
      nextQuery.q = normalizedNextQuery;
    } else {
      delete nextQuery.q;
    }

    nextQuery.search_field =
      searchField.value !== "all" ? searchField.value : undefined;
    router.replace({ query: nextQuery }).catch(() => {});
  }

  const suggestions = computed(() => {
    const queryText = normalizeText(searchKeyword.value);
    if (!queryText) return [];

    const candidates = allListings.value
      .flatMap((item) => [
        item.title,
        item.property?.full_address,
        item.property?.address_detail,
        item.property?.project_name,
      ])
      .filter(Boolean);

    return [...new Set(candidates)]
      .filter((text) => normalizeText(text).includes(queryText))
      .slice(0, 8);
  });

  watch(
    () => [
      posterType.value,
      minPrice.value,
      maxPrice.value,
      minArea.value,
      maxArea.value,
      sortBy.value,
      searchField.value,
      propertyType.value,
    ],
    () => {
      resetInfiniteScroll();
    },
  );

  watch(searchField, (nextField) => {
    const currentField = String(route?.query?.search_field || "all");
    const normalizedNextField = String(nextField || "all");
    const currentKeyword = String(route?.query?.q || "");

    searchKeyword.value = "";

    if (currentField === normalizedNextField && currentKeyword === "") return;

    router
      .replace({
        query: {
          ...route.query,
          q: undefined,
          search_field:
            normalizedNextField !== "all" ? normalizedNextField : undefined,
        },
      })
      .catch(() => {});
  });

  watch(sortBy, (nextSort) => {
    const currentSort = String(route?.query?.sort || "");
    const normalizedNextSort = String(nextSort || "");
    if (currentSort === normalizedNextSort) return;

    router
      .replace({
        query: {
          ...route.query,
          sort: normalizedNextSort || undefined,
        },
      })
      .catch(() => {});
  });

  watch(propertyType, (nextVal) => {
    const currentVal = String(route?.query?.property_type || "");
    const normalizedNextVal = String(nextVal || "");
    if (currentVal === normalizedNextVal) return;

    router
      .replace({
        query: {
          ...route.query,
          property_type: normalizedNextVal || undefined,
        },
      })
      .catch(() => {});
  });

  return {
    listings,
    isLoading,
    total,
    searchKeyword,
    searchField,
    suggestions,
    posterType,
    minPrice,
    maxPrice,
    minArea,
    maxArea,
    sortBy,
    propertyType,
    init,
    refetchListings,
    onSearch,
    // infinite scroll specific
    loadMore,
    hasMore,
    loadingError,
    isLoadingMore,
    isInitialLoading,
  };
}
