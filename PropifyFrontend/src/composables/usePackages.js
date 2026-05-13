import { ref } from 'vue';
import packageService from '@/services/packageService';

const packages = ref([]);
const loading = ref(true);
const error = ref('');
let initialized = false;

async function fetchPackages({ force = false } = {}) {
  if (!force && initialized && packages.value.length > 0) return;

  loading.value = true;
  error.value = '';
  try {
    const res = await packageService.getPackages();
    packages.value = res?.data?.data || [];
    initialized = true;
  } catch (err) {
    error.value = 'Không thể tải bảng giá. Vui lòng thử lại.';
    packages.value = [];
  } finally {
    loading.value = false;
  }
}

export function usePackages() {
  return {
    packages,
    loading,
    error,
    fetchPackages,
  };
}
