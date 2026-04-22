import { ref } from 'vue';
import api from '@/services/api';

export function usePackageApi() {
  const loading = ref(false);
  const error = ref(null);

  const createPackage = async (packageData) => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.post('/v1/packages', packageData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi tạo gói tin';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchPackages = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get('/v1/packages');
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi lấy danh sách gói tin';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchPackage = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get(`/v1/packages/${id}`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi lấy thông tin gói tin';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updatePackage = async (id, packageData) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.put(`/v1/packages/${id}`, packageData);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi cập nhật gói tin';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deletePackage = async (id) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.delete(`/v1/packages/${id}`);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi xóa gói tin';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    fetchPackages,
    fetchPackage,
    createPackage,
    updatePackage,
    deletePackage,
    loading,
    error,
  };
}
