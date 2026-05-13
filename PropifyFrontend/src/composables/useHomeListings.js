import { ref } from 'vue';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';

const saleListings = ref([]);
const rentListings = ref([]);
const saleLoading = ref(true);
const rentLoading = ref(true);

let saleInitialized = false;
let rentInitialized = false;

async function fetchSale() {
  if (saleInitialized && saleListings.value.length > 0) return;

  saleLoading.value = true;
  try {
    const res = await listingService.getPublicListings({ demand_type: 'SALE', per_page: 6 });
    const listings = res.data.data || [];
    await hydrateListingAddresses(listings);
    saleListings.value = listings;
    saleInitialized = true;
  } catch (e) {
    console.error('Failed to fetch sale listings', e);
  } finally {
    saleLoading.value = false;
  }
}

async function fetchRent() {
  if (rentInitialized && rentListings.value.length > 0) return;

  rentLoading.value = true;
  try {
    const res = await listingService.getPublicListings({ demand_type: 'RENT', per_page: 6 });
    const listings = res.data.data || [];
    await hydrateListingAddresses(listings);
    rentListings.value = listings;
    rentInitialized = true;
  } catch (e) {
    console.error('Failed to fetch rent listings', e);
  } finally {
    rentLoading.value = false;
  }
}

export function useHomeListings() {
  function init() {
    fetchSale();
    fetchRent();
  }

  return {
    saleListings,
    rentListings,
    saleLoading,
    rentLoading,
    init,
  };
}
