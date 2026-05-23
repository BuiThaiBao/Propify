import { computed } from 'vue';
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import { useRouter } from 'vue-router';
import favoriteService from '@/services/favoriteService';
import { useAuthStore } from '@/stores/auth';
import { favoriteKeys, listingKeys } from '@/composables/queryKeys';

function responseData(response) {
  return response?.data?.data || [];
}

function withFavoriteFlag(item, isFavorited) {
  return {
    ...item,
    is_favorited: isFavorited,
  };
}

export function useFavoriteListings() {
  const router = useRouter();
  const authStore = useAuthStore();
  const queryClient = useQueryClient();

  const favoriteIdsQuery = useQuery({
    queryKey: favoriteKeys.ids(),
    queryFn: async () => responseData(await favoriteService.getFavoriteIds()).map(Number),
    enabled: computed(() => authStore.isAuthenticated),
    staleTime: 60 * 1000,
    initialData: [],
  });

  const favoriteListingsQuery = useQuery({
    queryKey: favoriteKeys.list(),
    queryFn: async () => responseData(await favoriteService.getFavorites()).map((item) => withFavoriteFlag(item, true)),
    enabled: computed(() => authStore.isAuthenticated),
    staleTime: 60 * 1000,
    initialData: [],
  });

  const favoriteIds = computed(() => new Set((favoriteIdsQuery.data.value || []).map(Number)));
  const favoriteListings = computed(() => favoriteListingsQuery.data.value || []);
  const favoriteLoading = computed(() => favoriteIdsQuery.isFetching.value || favoriteListingsQuery.isFetching.value);

  const toggleMutation = useMutation({
    mutationFn: async ({ id }) => {
      const response = await favoriteService.toggle(id);
      return Boolean(response.data?.data?.is_favorited);
    },
    onMutate: async ({ item, id, nextValue }) => {
      await Promise.all([
        queryClient.cancelQueries({ queryKey: favoriteKeys.all }),
        queryClient.cancelQueries({ queryKey: listingKeys.public() }),
      ]);

      const previousIds = queryClient.getQueryData(favoriteKeys.ids()) || [];
      const previousFavorites = queryClient.getQueryData(favoriteKeys.list()) || [];

      setFavoriteState(item, id, nextValue);

      return { previousIds, previousFavorites };
    },
    onError: (_error, _variables, context) => {
      if (!context) return;
      queryClient.setQueryData(favoriteKeys.ids(), context.previousIds);
      queryClient.setQueryData(favoriteKeys.list(), context.previousFavorites);
      queryClient.invalidateQueries({ queryKey: listingKeys.public() });
    },
    onSuccess: (isFavorited, { item, id }) => {
      setFavoriteState(item, id, isFavorited);
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: favoriteKeys.all });
      queryClient.invalidateQueries({ queryKey: listingKeys.public() });
    },
  });

  function isFavorite(item) {
    return Boolean(item?.is_favorited) || favoriteIds.value.has(Number(item?.id));
  }

  function toggleFavorite(item) {
    if (!authStore.isAuthenticated) {
      router.push({ name: 'Login', query: { redirect: `/listings/${item.id}` } });
      return;
    }

    const id = Number(item.id);
    toggleMutation.mutate({
      item,
      id,
      nextValue: !isFavorite(item),
    });
  }

  function loadFavorites() {
    if (!authStore.isAuthenticated) {
      queryClient.setQueryData(favoriteKeys.ids(), []);
      queryClient.setQueryData(favoriteKeys.list(), []);
      return;
    }

    favoriteIdsQuery.refetch();
    favoriteListingsQuery.refetch();
  }

  function setFavoriteState(item, id, isFavorited) {
    queryClient.setQueryData(favoriteKeys.ids(), (old = []) => {
      const next = new Set(old.map(Number));
      if (isFavorited) next.add(id);
      else next.delete(id);
      return [...next];
    });

    queryClient.setQueryData(favoriteKeys.list(), (old = []) => {
      if (!isFavorited) {
        return old.filter((favorite) => Number(favorite.id) !== id);
      }

      const optimisticItem = withFavoriteFlag(item, true);
      const exists = old.some((favorite) => Number(favorite.id) === id);
      if (exists) {
        return old.map((favorite) => Number(favorite.id) === id ? optimisticItem : favorite);
      }
      return [optimisticItem, ...old];
    });

    queryClient.setQueriesData({ queryKey: listingKeys.public() }, (old) => updateListingFavorite(old, id, isFavorited));
  }

  return {
    favoriteIds,
    favoriteListings,
    favoriteLoading,
    isFavorite,
    toggleFavorite,
    loadFavorites,
  };
}

function updateListingFavorite(old, id, isFavorited) {
  if (!old) return old;

  if (Array.isArray(old)) {
    return old.map((item) => updateListingFavoriteItem(item, id, isFavorited));
  }

  if (Array.isArray(old.data)) {
    return {
      ...old,
      data: old.data.map((item) => updateListingFavoriteItem(item, id, isFavorited)),
    };
  }

  return old;
}

function updateListingFavoriteItem(item, id, isFavorited) {
  if (Number(item?.id) !== id) return item;
  return withFavoriteFlag(item, isFavorited);
}
