<template>
  <div class="package-detail-page">
    <div class="detail-heading">
      <div>
        <p class="breadcrumb">Quan ly goi tin / Chi tiet</p>
        <h1>{{ packageData?.name || 'Chi tiet goi tin' }}</h1>
        <p class="subtitle">Xem cau hinh hien thi, xep hang va bang gia theo thoi han.</p>
      </div>
      <div class="heading-actions">
        <Button variant="outline" @click="router.push({ name: 'Packages' })">Quay lai</Button>
        <Button v-if="packageData" variant="primary" @click="router.push({ name: 'PackageEdit', params: { id: packageId } })">
          Chinh sua
        </Button>
      </div>
    </div>

    <div v-if="loading && !packageData" class="state-card">Dang tai thong tin goi tin...</div>
    <div v-else-if="fetchError" class="state-card state-error">{{ fetchError }}</div>

    <template v-else-if="packageData">
      <section class="hero-card">
        <div class="hero-icon" :style="packageData.color ? { backgroundColor: `${packageData.color}20` } : null">
          <Star :size="34" :color="packageData.color || '#3b82f6'" />
        </div>

        <div class="hero-main">
          <div class="title-row">
            <h2>{{ packageData.name }}</h2>
            <span v-if="packageData.badge" class="badge">{{ packageData.badge }}</span>
            <span :class="['status-pill', packageData.is_active ? 'status-active' : 'status-locked']">
              {{ packageData.is_active ? 'Dang hoat dong' : 'Da khoa' }}
            </span>
          </div>
          <p class="slug">Slug: {{ packageData.slug }}</p>
          <p class="price">{{ formatPrice(packageData.price) }} <span>/ ngay</span></p>
        </div>
      </section>

      <section class="metrics-grid">
        <div class="metric-card">
          <span>Do uu tien</span>
          <strong>{{ packageData.priority }}</strong>
        </div>
        <div class="metric-card">
          <span>He so diem</span>
          <strong>{{ packageData.multiplier }}x</strong>
        </div>
        <div class="metric-card">
          <span>Luot hien thi/ngay</span>
          <strong>{{ packageData.daily_quota }}</strong>
        </div>
        <div class="metric-card">
          <span>Toc do tut hang</span>
          <strong>{{ packageData.decay_rate }}</strong>
        </div>
        <div class="metric-card">
          <span>Tin dang dang dung</span>
          <strong>{{ packageData.listings_count || 0 }}</strong>
        </div>
        <div class="metric-card">
          <span>Giao dich</span>
          <strong>{{ packageData.transactions_count || 0 }}</strong>
        </div>
      </section>

      <section class="content-grid">
        <article class="panel">
          <div class="panel-header">
            <h3>Bang gia theo thoi han</h3>
            <span>{{ activePricings.length }} muc dang bat</span>
          </div>
          <table class="pricing-table">
            <thead>
              <tr>
                <th>Thoi han</th>
                <th>Gia</th>
                <th>Trang thai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pricing in sortedPricings" :key="pricing.id">
                <td>{{ pricing.label || `${pricing.duration_days} ngay` }}</td>
                <td>{{ formatPrice(pricing.price) }}</td>
                <td>
                  <span :class="['mini-pill', pricing.is_active ? 'mini-active' : 'mini-muted']">
                    {{ pricing.is_active ? 'Dang bat' : 'Dang tat' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </article>

        <article class="panel">
          <div class="panel-header">
            <h3>Thong tin he thong</h3>
          </div>
          <dl class="info-list">
            <div>
              <dt>ID</dt>
              <dd>#{{ packageData.id }}</dd>
            </div>
            <div>
              <dt>Mau hien thi</dt>
              <dd>
                <span v-if="packageData.color" class="color-dot" :style="{ backgroundColor: packageData.color }"></span>
                {{ packageData.color || 'Chua cau hinh' }}
              </dd>
            </div>
            <div>
              <dt>Ngay tao</dt>
              <dd>{{ formatDate(packageData.created_at) }}</dd>
            </div>
            <div>
              <dt>Cap nhat lan cuoi</dt>
              <dd>{{ formatDate(packageData.updated_at) }}</dd>
            </div>
          </dl>
        </article>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Star } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import { usePackageApi } from '@/composables/usePackageApi'

const route = useRoute()
const router = useRouter()
const packageId = route.params.id
const { fetchPackage, loading } = usePackageApi()

const packageData = ref(null)
const fetchError = ref('')

const sortedPricings = computed(() => {
  return [...(packageData.value?.pricings || [])].sort((a, b) => Number(a.duration_days) - Number(b.duration_days))
})

const activePricings = computed(() => sortedPricings.value.filter((pricing) => pricing.is_active))

function formatPrice(price) {
  return Number(price || 0).toLocaleString('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  })
}

function formatDate(value) {
  if (!value) return 'Chua co'
  return new Date(value).toLocaleString('vi-VN')
}

onMounted(async () => {
  try {
    const response = await fetchPackage(packageId)
    packageData.value = response?.data || null
  } catch (error) {
    fetchError.value = error.response?.data?.message || 'Khong the tai chi tiet goi tin'
  }
})
</script>

<style scoped>
.package-detail-page {
  max-width: 1120px;
}

.detail-heading,
.heading-actions,
.title-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.detail-heading {
  justify-content: space-between;
  margin-bottom: 22px;
}

.breadcrumb {
  margin: 0 0 6px;
  color: #64748b;
  font-size: 13px;
}

.detail-heading h1 {
  margin: 0;
  color: #0f172a;
  font-size: 28px;
  font-weight: 800;
}

.subtitle {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 14px;
}

.state-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  padding: 26px;
  color: #64748b;
}

.state-error {
  color: #dc2626;
}

.hero-card {
  display: flex;
  align-items: center;
  gap: 20px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  padding: 24px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

.hero-icon {
  display: flex;
  width: 74px;
  height: 74px;
  align-items: center;
  justify-content: center;
  border-radius: 18px;
  background: #eff6ff;
}

.hero-main h2 {
  margin: 0;
  color: #0f172a;
  font-size: 24px;
  font-weight: 800;
}

.slug {
  margin: 5px 0 10px;
  color: #64748b;
  font-size: 13px;
}

.price {
  margin: 0;
  color: #2563eb;
  font-size: 30px;
  font-weight: 800;
}

.price span {
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
}

.badge,
.status-pill,
.mini-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.badge {
  background: #fee2e2;
  color: #dc2626;
  padding: 4px 9px;
}

.status-pill {
  padding: 5px 10px;
}

.status-active,
.mini-active {
  background: #dcfce7;
  color: #15803d;
}

.status-locked {
  background: #f1f5f9;
  color: #64748b;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 12px;
  margin: 18px 0;
}

.metric-card,
.panel {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
}

.metric-card {
  padding: 14px;
}

.metric-card span {
  display: block;
  min-height: 32px;
  color: #64748b;
  font-size: 12px;
}

.metric-card strong {
  color: #0f172a;
  font-size: 22px;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(300px, 0.8fr);
  gap: 18px;
}

.panel {
  padding: 18px;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.panel-header h3 {
  margin: 0;
  color: #0f172a;
  font-size: 17px;
}

.panel-header span {
  color: #64748b;
  font-size: 13px;
}

.pricing-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.pricing-table th,
.pricing-table td {
  border-bottom: 1px solid #e2e8f0;
  padding: 12px 10px;
  text-align: left;
}

.pricing-table th {
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.mini-pill {
  padding: 4px 9px;
}

.mini-muted {
  background: #f1f5f9;
  color: #64748b;
}

.info-list {
  margin: 0;
}

.info-list div {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  border-bottom: 1px solid #f1f5f9;
  padding: 12px 0;
}

.info-list dt {
  color: #64748b;
  font-size: 13px;
}

.info-list dd {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0;
  color: #0f172a;
  font-size: 13px;
  font-weight: 700;
  text-align: right;
}

.color-dot {
  width: 12px;
  height: 12px;
  border-radius: 999px;
}

@media (max-width: 1100px) {
  .metrics-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 720px) {
  .detail-heading,
  .heading-actions,
  .hero-card,
  .title-row {
    align-items: stretch;
    flex-direction: column;
  }

  .metrics-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
