<template>
  <div class="mx-auto mt-20 mb-8 flex min-h-[calc(100vh-220px)] max-w-[1240px] gap-6 px-4 max-lg:flex-col">
    <aside class="w-full max-w-[280px] shrink-0 rounded-2xl border border-slate-200 bg-white max-lg:max-w-full">
      <div class="border-b border-slate-100 px-5 py-6 text-center">
        <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-sky-100 text-sky-500">
          <img
            v-if="authStore.user?.avatar_url"
            :src="authStore.user.avatar_url"
            alt="Avatar"
            class="h-full w-full object-cover"
          />
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            width="28"
            height="28"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-800">{{ authStore.user?.full_name || "Người dùng" }}</h3>
        <p class="truncate text-xs text-slate-400">{{ authStore.user?.email }}</p>
      </div>

      <div class="p-2">
        <button
          class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-sky-600"
          @click="router.push('/profile')"
        >
          <span>Thông tin tài khoản</span>
        </button>
        <button
          class="flex w-full items-center gap-2 rounded-xl bg-sky-50 px-3 py-2.5 text-sm font-semibold text-sky-600"
          type="button"
        >
          <span>Danh sách tin đăng</span>
        </button>
      </div>
    </aside>

    <main class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white p-4 lg:p-6">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs text-slate-400">Trang chủ > Quản lý tin đăng</p>
          <h1 class="mt-1 text-2xl font-bold text-slate-900">Danh sách tin đăng</h1>
        </div>
      </div>

      <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-[1fr_auto_auto]">
        <input
          v-model.trim="filters.keyword"
          type="text"
          class="h-10 rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-400"
          placeholder="Nhập giá trị tìm kiếm..."
          @keyup.enter="reload(1)"
        />

        <select
          v-model="filters.status"
          class="h-10 rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-400"
          @change="reload(1)"
        >
          <option value="">Trạng thái: Tất cả</option>
          <option value="PENDING">Chờ duyệt</option>
          <option value="ACTIVE">Đang đăng</option>
          <option value="REJECTED">Từ chối</option>
          <option value="LOCKED">Tin bị khóa</option>
          <option value="DRAFT">Tin nháp</option>
          <option value="EXPIRED">Hết hạn</option>
        </select>

        <select
          v-model="filters.demandType"
          class="h-10 rounded-xl border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-400"
          @change="reload(1)"
        >
          <option value="">Loại tin: Tất cả</option>
          <option value="SALE">Mua bán</option>
          <option value="RENT">Cho thuê</option>
        </select>
      </div>

      <div class="mb-4 flex flex-wrap gap-2">
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          type="button"
          :class="[
            'rounded-full border px-3 py-1.5 text-xs font-medium transition',
            filters.status === tab.value
              ? 'border-sky-500 bg-sky-50 text-sky-600'
              : 'border-slate-200 text-slate-500 hover:bg-slate-50',
          ]"
          @click="selectStatus(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left text-xs text-slate-500">
            <tr>
              <th class="px-3 py-3">ID</th>
              <th class="px-3 py-3">Ảnh</th>
              <th class="px-3 py-3">Mã tin đăng</th>
              <th class="px-3 py-3">Tin đăng</th>
              <th class="px-3 py-3">Địa chỉ</th>
              <th class="px-3 py-3">Giá</th>
              <th class="px-3 py-3">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td class="px-3 py-6 text-center text-slate-400" colspan="7">Đang tải dữ liệu...</td>
            </tr>
            <tr v-else-if="rows.length === 0">
              <td class="px-3 py-6 text-center text-slate-400" colspan="7">Bạn chưa có tin đăng nào.</td>
            </tr>
            <tr v-for="row in rows" :key="row.id" class="border-t border-slate-100">
              <td class="px-3 py-3 font-medium text-sky-600">{{ row.id }}</td>
              <td class="px-3 py-3">
                <img
                  v-if="row.thumbnail"
                  :src="row.thumbnail"
                  alt="thumb"
                  class="h-12 w-14 rounded-md object-cover"
                />
                <div v-else class="h-12 w-14 rounded-md bg-slate-100"></div>
              </td>
              <td class="px-3 py-3 text-slate-500">{{ row.code }}</td>
              <td class="px-3 py-3 text-slate-700">{{ row.title }}</td>
              <td class="px-3 py-3 text-slate-500">{{ row.address }}</td>
              <td class="px-3 py-3 font-semibold text-slate-700">{{ row.price }}</td>
              <td class="px-3 py-3">
                <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusBadgeClass(row.status)]">
                  {{ statusLabel(row.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
        <p>Tổng cộng {{ pagination.total }} tin</p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
            :disabled="pagination.currentPage <= 1 || loading"
            @click="reload(pagination.currentPage - 1)"
          >
            Trước
          </button>
          <span>Trang {{ pagination.currentPage }}/{{ pagination.lastPage }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
            :disabled="pagination.currentPage >= pagination.lastPage || loading"
            @click="reload(pagination.currentPage + 1)"
          >
            Sau
          </button>
          <select
            v-model.number="pagination.perPage"
            class="rounded-lg border border-slate-200 px-2 py-1.5"
            @change="reload(1)"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import listingService from "@/services/listingService";

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(false);
const rows = ref([]);

const filters = reactive({
  keyword: "",
  status: "",
  demandType: "",
});

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 0,
});

const statusTabs = computed(() => [
  { value: "", label: "Tất cả" },
  { value: "PENDING", label: "Chờ duyệt" },
  { value: "ACTIVE", label: "Tin đang đăng" },
  { value: "DRAFT", label: "Tin nháp" },
  { value: "REJECTED", label: "Từ chối" },
  { value: "LOCKED", label: "Tin bị khóa" },
]);

function formatCurrency(value) {
  const number = Number(value || 0);
  if (!Number.isFinite(number) || number <= 0) return "Thỏa thuận";
  return number.toLocaleString("vi-VN");
}

function toListingCode(id) {
  return `LH-${String(id).padStart(8, "0")}`;
}

function buildAddress(property) {
  if (!property) return "";
  return property.address_detail || "";
}

function statusLabel(status) {
  const map = {
    DRAFT: "Tin nháp",
    PENDING: "Chờ duyệt",
    ACTIVE: "Đang đăng",
    EXPIRED: "Hết hạn",
    REJECTED: "Từ chối",
    LOCKED: "Tin bị khóa",
  };
  return map[status] || status;
}

function statusBadgeClass(status) {
  const map = {
    DRAFT: "bg-slate-100 text-slate-600",
    PENDING: "bg-amber-100 text-amber-700",
    ACTIVE: "bg-emerald-100 text-emerald-700",
    EXPIRED: "bg-slate-200 text-slate-600",
    REJECTED: "bg-rose-100 text-rose-700",
    LOCKED: "bg-red-100 text-red-700",
  };
  return map[status] || "bg-slate-100 text-slate-600";
}

function normalizeRows(items) {
  return items.map((item) => {
    const thumbnail = item?.images?.find((img) => img?.is_thumbnail)?.url || item?.images?.[0]?.url || "";
    return {
      id: item.id,
      code: toListingCode(item.id),
      title: item.title || "(Không tiêu đề)",
      thumbnail,
      address: buildAddress(item.property),
      price: formatCurrency(item?.property?.price),
      status: item.status,
    };
  });
}

async function reload(page = 1) {
  loading.value = true;
  try {
    const response = await listingService.getMyListings({
      page,
      per_page: pagination.perPage,
      keyword: filters.keyword || undefined,
      status: filters.status || undefined,
      demand_type: filters.demandType || undefined,
    });

    const data = response?.data?.data || [];
    const meta = response?.data?.meta || {};

    rows.value = normalizeRows(data);
    pagination.currentPage = Number(meta.current_page || 1);
    pagination.lastPage = Number(meta.last_page || 1);
    pagination.perPage = Number(meta.per_page || pagination.perPage);
    pagination.total = Number(meta.total || 0);
  } catch {
    rows.value = [];
    pagination.currentPage = 1;
    pagination.lastPage = 1;
    pagination.total = 0;
  } finally {
    loading.value = false;
  }
}

function selectStatus(status) {
  filters.status = status;
  reload(1);
}

onMounted(() => {
  reload(1);
});
</script>
