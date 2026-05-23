<template>
  <main class="min-h-screen bg-slate-50 grid place-items-center px-4">
    <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-7 text-center shadow-sm">
      <div
        class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full text-2xl font-bold text-white"
        :class="isSuccess ? 'bg-emerald-500' : 'bg-rose-500'"
      >
        {{ isSuccess ? '✓' : '!' }}
      </div>
      <h1 class="mb-2 text-xl font-bold text-slate-900">
        {{ isSuccess ? 'Thanh toán thành công' : 'Thanh toán chưa thành công' }}
      </h1>
      <p class="text-sm leading-6 text-slate-500">
        Đang chuyển về trang quản lý tin đăng...
      </p>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const isSuccess = computed(() => route.query.status === 'success');

onMounted(() => {
  setTimeout(() => {
    router.replace({
      path: '/profile',
      query: {
        tab: 'listings',
        payment: isSuccess.value ? 'success' : 'failed',
        transaction_id: route.query.transaction_id,
      },
    });
  }, 800);
});
</script>
