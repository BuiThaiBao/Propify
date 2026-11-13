<template>
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4">
    <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-7 text-center shadow-sm">
      <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full text-2xl font-bold text-white"
        :class="status === 'SUCCESS' ? 'bg-emerald-500' : redirecting ? 'bg-rose-500' : 'bg-sky-500'">
        {{ status === 'SUCCESS' ? '✓' : redirecting ? '!' : '…' }}
      </div>
      <h1 class="mb-2 text-xl font-bold text-slate-900" role="status">{{ message }}</h1>
      <p v-if="redirecting" class="text-sm text-slate-500">Đang chuyển về trang quản lý tin đăng...</p>
      <button v-else-if="canCheck" type="button" :disabled="checking" class="mt-3 font-medium text-blue-700" @click="check">
        {{ checking ? 'Đang kiểm tra…' : 'Kiểm tra lại' }}
      </button>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import listingService from '@/services/listingService';

const route = useRoute();
const router = useRouter();
const listingId = route.query.listing_id;
const transactionId = route.query.transaction_id;
const validIds = [listingId, transactionId].every(value => typeof value === 'string' && /^[1-9]\d*$/.test(value));
const status = ref('PENDING');
const checking = ref(false);
const failedCheck = ref(false);
const redirecting = ref(false);
const canCheck = computed(() => validIds && status.value === 'PENDING' && !redirecting.value);
let timer;
let attempts = 0;
let active = true;

const message = computed(() => {
  if (!validIds) return 'Thiếu thông tin đơn thanh toán.';
  if (status.value === 'SUCCESS') return 'Thanh toán thành công. Gói tin đã được cập nhật.';
  if (status.value === 'EXPIRED') return 'Đơn thanh toán đã hết hạn.';
  if (failedCheck.value) return 'Chưa kiểm tra được trạng thái. Bạn có thể thử lại.';
  if (route.query.result === 'cancel') return 'Bạn đã hủy thao tác thanh toán.';
  if (route.query.result === 'error') return 'SePay báo lỗi khi thanh toán.';
  return 'Đang chờ SePay xác nhận giao dịch.';
});

async function check() {
  if (!canCheck.value || checking.value) return;
  clearTimeout(timer);
  checking.value = true;
  failedCheck.value = false;
  try {
    const response = await listingService.getUpgradePaymentStatus(listingId, transactionId);
    status.value = response.data.data.status;
  } catch {
    failedCheck.value = true;
  } finally {
    checking.value = false;
  }

  if (!active) return;
  const terminal = status.value !== 'PENDING';
  const returnedWithoutPayment = !failedCheck.value && ['cancel', 'error'].includes(route.query.result);
  if (terminal || returnedWithoutPayment) {
    redirecting.value = true;
    timer = setTimeout(() => router.replace({
      name: 'Profile',
      query: {
        tab: 'listings',
        payment: status.value === 'SUCCESS' ? 'success' : 'failed',
        transaction_id: transactionId,
      },
    }), 800);
  } else if (canCheck.value && route.query.result === 'success' && ++attempts < 10) {
    timer = setTimeout(check, 3000);
  }
}

onMounted(check);
onUnmounted(() => {
  active = false;
  clearTimeout(timer);
});
</script>
