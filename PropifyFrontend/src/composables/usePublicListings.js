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
    .replace(/[\u0300-\u036f]/g, "")
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
  const currentPage = ref(1);
  const searchKeyword = ref(route?.query?.q || "");
  const searchField = ref(String(route?.query?.search_field || "all"));
  const posterType = ref("");
  const minPrice = ref(null);
  const maxPrice = ref(null);
  const minArea = ref(null);
  const maxArea = ref(null);
  const sortBy = ref(String(route?.query?.sort || ""));

  watch(
    () => route?.query?.q,
    (newVal) => {
      searchKeyword.value = newVal || "";
      currentPage.value = 1;
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
      currentPage.value = 1;
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
  });

  const queryKey = computed(() =>
    listingKeys.publicList(createPayload(currentPage.value)),
  );

  const query = useQuery({
    queryKey,
    queryFn: () =>
      fetchPublicListingsPage({
        demandType,
        pageSize,
        page: currentPage.value,
        keyword: searchKeyword.value,
        searchField: searchField.value,
        posterType: posterType.value,
        minPrice: minPrice.value,
        maxPrice: maxPrice.value,
        minArea: minArea.value,
        maxArea: maxArea.value,
        sortBy: sortBy.value,
      }),
    enabled,
    placeholderData: keepPreviousData,
    staleTime: 60 * 1000,
  });

  const listings = computed(() => query.data.value?.data || []);
  const total = computed(() =>
    Number(query.data.value?.meta?.total || listings.value.length || 0),
  );
  const lastPage = computed(() =>
    Number(query.data.value?.meta?.last_page || 1),
  );
  const isLoading = computed(
    () => query.isLoading.value || query.isFetching.value,
  );

  const suggestions = computed(() => {
    const queryText = normalizeText(searchKeyword.value);
    if (!queryText) return [];

    const candidates = listings.value
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

  const visiblePages = computed(() => {
    const totalPages = lastPage.value;
    const current = currentPage.value;

    if (totalPages <= 7) {
      return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const pages = [1];
    if (current > 3) pages.push("...");

    const start = Math.max(2, current - 1);
    const end = Math.min(totalPages - 1, current + 1);
    for (let page = start; page <= end; page += 1) {
      pages.push(page);
    }

    if (current < totalPages - 2) pages.push("...");
    pages.push(totalPages);

    return pages;
  });

  watch(
    () => [
      enabled.value,
      currentPage.value,
      searchKeyword.value,
      searchField.value,
      lastPage.value,
      posterType.value,
      minPrice.value,
      maxPrice.value,
      minArea.value,
      maxArea.value,
      sortBy.value,
    ],
    ([
      isEnabled,
      page,
      keyword,
      selectedField,
      totalPages,
      nextPosterType,
      nextMinPrice,
      nextMaxPrice,
      nextMinArea,
      nextMaxArea,
      nextSort,
    ]) => {
      if (!isEnabled) return;

      const nextPage = Number(page) + 1;
      if (nextPage > Number(totalPages || 1)) return;

      queryClient.prefetchQuery({
        queryKey: listingKeys.publicList({
          demand_type: demandType,
          keyword: keyword?.trim(),
          search_field:
            selectedField && selectedField !== "all"
              ? selectedField
              : undefined,
          per_page: pageSize,
          page: nextPage,
          poster_type: nextPosterType || undefined,
          min_price: nextMinPrice !== null ? nextMinPrice : undefined,
          max_price: nextMaxPrice !== null ? nextMaxPrice : undefined,
          min_area: nextMinArea !== null ? nextMinArea : undefined,
          max_area: nextMaxArea !== null ? nextMaxArea : undefined,
          sort: nextSort || undefined,
        }),
        queryFn: () =>
          fetchPublicListingsPage({
            demandType,
            pageSize,
            page: nextPage,
            keyword,
            searchField: selectedField,
            posterType: nextPosterType,
            minPrice: nextMinPrice,
            maxPrice: nextMaxPrice,
            minArea: nextMinArea,
            maxArea: nextMaxArea,
            sortBy: nextSort,
          }),
        staleTime: 60 * 1000,
      });
    },
  );

  watch(
    () => [
      posterType.value,
      minPrice.value,
      maxPrice.value,
      minArea.value,
      maxArea.value,
      sortBy.value,
      searchField.value,
    ],
    () => {
      currentPage.value = 1;
    },
  );

  watch(searchField, (nextField) => {
    const currentField = String(route?.query?.search_field || "all");
    const normalizedNextField = String(nextField || "all");
    const currentKeyword = String(route?.query?.q || "");

    searchKeyword.value = "";
    currentPage.value = 1;

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

  function init() {
    enabled.value = true;
  }

  function refetchListings() {
    enabled.value = true;
    return query.refetch();
  }

  function onSearch(value) {
    searchKeyword.value = value || "";
    currentPage.value = 1;

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

  function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) return;
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  return {
    listings,
    isLoading,
    total,
    currentPage,
    lastPage,
    searchKeyword,
    searchField,
    suggestions,
    visiblePages,
    posterType,
    minPrice,
    maxPrice,
    minArea,
    maxArea,
    sortBy,
    init,
    refetchListings,
    onSearch,
    goToPage,
  };
}
