<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="visible" class="modal-overlay" @click.self="close">
        <div class="modal-container">
          <!-- Header -->
          <div class="modal-header">
            <div>
              <h2 class="modal-title">🚀 Nâng cấp gói tin</h2>
              <p class="modal-subtitle">Chọn gói tin phù hợp để tăng hiệu quả hiển thị</p>
            </div>
            <button class="modal-close" @click="close" aria-label="Đóng">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="modal-loading">
            <div class="spinner"></div>
            <p>Đang tải gói tin...</p>
          </div>

          <!-- Package Cards -->
          <div v-else class="packages-grid">
            <div
              v-for="pkg in sortedPackages"
              :key="pkg.id"
              :class="[
                'package-card',
                { 'package-card--current': isCurrent(pkg) },
                { 'package-card--selected': selectedPkg?.id === pkg.id },
                { 'package-card--gold': pkg.slug === 'gold' },
                { 'package-card--silver': pkg.slug === 'silver' },
                { 'package-card--basic': pkg.slug === 'basic' },
              ]"
            >
              <!-- Badge -->
              <div v-if="pkg.badge" class="package-badge" :style="{ background: pkg.color }">
                {{ pkg.badge }}
              </div>

              <!-- Name -->
              <h3 class="package-name">{{ pkg.name }}</h3>

              <!-- Features -->
              <ul class="package-features">
                <li>
                  <span class="feature-icon">⚡</span>
                  Ưu tiên hiển thị: <strong>Tầng {{ pkg.priority }}</strong>
                </li>
                <li>
                  <span class="feature-icon">📈</span>
                  Hệ số điểm: <strong>×{{ pkg.multiplier }}</strong>
                </li>
                <li>
                  <span class="feature-icon">👁️</span>
                  Lượt hiển thị/ngày: <strong>{{ pkg.daily_quota }}</strong>
                </li>
              </ul>

              <!-- Duration picker (shown when package has pricings) -->
              <div v-if="pkg.pricings?.length" class="duration-picker">
                <label class="duration-label">Chọn thời hạn:</label>
                <div class="duration-options">
                  <button
                    v-for="pricing in pkg.pricings"
                    :key="pricing.id"
                    :class="['duration-btn', { 'duration-btn--active': selectedPricing?.id === pricing.id && selectedPkg?.id === pkg.id }]"
                    @click="selectPricing(pkg, pricing)"
                  >
                    <span class="duration-days">{{ pricing.label }}</span>
                    <span class="duration-price">{{ formatPrice(pricing.price) }}đ</span>
                  </button>
                </div>
              </div>
              <div v-else class="no-pricing">
                Chưa có bảng giá
              </div>

              <!-- Action -->
              <button
                v-if="canUpgrade(pkg) || isCurrent(pkg)"
                :class="['package-btn', selectedPkg?.id === pkg.id && selectedPricing ? 'package-btn--upgrade' : 'package-btn--disabled']"
                :disabled="upgrading || !(selectedPkg?.id === pkg.id && selectedPricing)"
                @click="doUpgrade(pkg)"
              >
                <template v-if="upgrading && upgradingPkgId === pkg.id">
                  <div class="btn-spinner"></div> Đang xử lý...
                </template>
                <template v-else-if="isCurrent(pkg)">
                  🔄 Gia hạn {{ pkg.name }}
                </template>
                <template v-else>
                  Nâng cấp lên {{ pkg.name }}
                </template>
              </button>
              <button
                v-else
                class="package-btn package-btn--disabled"
                disabled
              >
                Không thể chọn
              </button>
            </div>
          </div>

          <!-- Current package info -->
          <div v-if="currentPackageName" class="current-info">
            Tin đăng đang sử dụng gói: <strong>{{ currentPackageName }}</strong>
            <span v-if="selectedPricing" class="selected-summary">
              → {{ selectedPkg?.name }} · {{ selectedPricing.label }} · <strong>{{ formatPrice(selectedPricing.price) }}đ</strong>
            </span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import packageService from '@/services/packageService';
import listingService from '@/services/listingService';

const props = defineProps({
  visible: { type: Boolean, default: false },
  listingId: { type: [Number, null], default: null },
  currentPackageId: { type: [Number, null], default: null },
});

const emit = defineEmits(['close', 'upgraded']);

const loading = ref(false);
const packages = ref([]);
const upgrading = ref(false);
const upgradingPkgId = ref(null);
const selectedPkg = ref(null);
const selectedPricing = ref(null);

const sortedPackages = computed(() => {
  return [...packages.value].sort((a, b) => a.priority - b.priority);
});

const currentPackageName = computed(() => {
  if (!props.currentPackageId) return 'Cơ bản (Chưa mua gói)';
  const pkg = packages.value.find(p => p.id === props.currentPackageId);
  return pkg?.name || 'Cơ bản';
});

watch(() => props.visible, async (val) => {
  if (val) {
    selectedPkg.value = null;
    selectedPricing.value = null;
    await fetchPackages();
  }
});

async function fetchPackages() {
  loading.value = true;
  try {
    const res = await packageService.getPackages();
    packages.value = res?.data?.data || [];
  } catch {
    packages.value = [];
  } finally {
    loading.value = false;
  }
}

function isCurrent(pkg) {
  return pkg.id === props.currentPackageId;
}

function canUpgrade(pkg) {
  const currentPriority = getCurrentPriority();
  // Cho phép gia hạn cùng gói hoặc nâng cấp lên gói cao hơn
  return pkg.priority > currentPriority || isCurrent(pkg);
}

function getCurrentPriority() {
  if (!props.currentPackageId) return 0;
  const current = packages.value.find(p => p.id === props.currentPackageId);
  return current?.priority ?? 0;
}

function selectPricing(pkg, pricing) {
  selectedPkg.value = pkg;
  selectedPricing.value = pricing;
}

async function doUpgrade(pkg) {
  if (!props.listingId || upgrading.value || !selectedPricing.value) return;

  const action = isCurrent(pkg) ? 'gia hạn' : 'nâng cấp lên';
  const confirmed = window.confirm(
    `Bạn có chắc muốn ${action} gói "${pkg.name}" - ${selectedPricing.value.label} với giá ${formatPrice(selectedPricing.value.price)}đ?`
  );
  if (!confirmed) return;

  upgrading.value = true;
  upgradingPkgId.value = pkg.id;

  try {
    await listingService.upgradeListing(props.listingId, pkg.id, selectedPricing.value.duration_days);
    alert('🎉 ' + (isCurrent(pkg) ? 'Gia hạn' : 'Nâng cấp') + ' gói tin thành công!');
    emit('upgraded', pkg);
    close();
  } catch (err) {
    const msg = err?.response?.data?.message || 'Thao tác thất bại. Vui lòng thử lại.';
    alert('❌ ' + msg);
  } finally {
    upgrading.value = false;
    upgradingPkgId.value = null;
  }
}

function close() {
  emit('close');
}

function formatPrice(value) {
  return Number(value || 0).toLocaleString('vi-VN');
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-container {
  background: #fff;
  border-radius: 20px;
  max-width: 860px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
  animation: modal-slide-up 0.3s ease;
}

@keyframes modal-slide-up {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px 28px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 4px 0 0;
}

.modal-close {
  background: #f1f5f9;
  border: none;
  border-radius: 10px;
  padding: 8px;
  cursor: pointer;
  color: #475569;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.modal-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 48px;
  color: #64748b;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.packages-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  padding: 24px 28px;
}

@media (max-width: 768px) {
  .packages-grid { grid-template-columns: 1fr; }
}

.package-card {
  position: relative;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px 20px;
  text-align: center;
  transition: all 0.3s ease;
  background: #fff;
}

.package-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
}

.package-card--current {
  border-color: #3b82f6;
  background: #eff6ff;
}

.package-card--gold {
  border-color: #fbbf24;
}

.package-card--gold:hover {
  box-shadow: 0 12px 32px rgba(251, 191, 36, 0.2);
}

.package-card--silver {
  border-color: #94a3b8;
}

.package-badge {
  position: absolute;
  top: -10px;
  right: 16px;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 8px;
  letter-spacing: 0.5px;
  text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.package-name {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 8px 0 12px;
}

.package-price {
  margin-bottom: 20px;
}

.price-value {
  font-size: 28px;
  font-weight: 800;
  color: #1e40af;
}

.price-unit {
  font-size: 14px;
  color: #64748b;
  margin-left: 4px;
}

.price-free {
  font-size: 24px;
  font-weight: 700;
  color: #16a34a;
}

.package-features {
  list-style: none;
  padding: 0;
  margin: 0 0 20px;
  text-align: left;
}

.package-features li {
  padding: 6px 0;
  font-size: 13px;
  color: #475569;
  border-bottom: 1px solid #f8fafc;
}

.package-features li:last-child {
  border-bottom: none;
}

.feature-icon {
  margin-right: 6px;
}

.package-btn {
  width: 100%;
  padding: 10px 16px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.package-btn--current {
  background: #dbeafe;
  color: #2563eb;
  cursor: default;
}

.package-btn--upgrade {
  background: #2563eb;
  color: #fff;
}

.package-btn--upgrade:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.package-btn--upgrade:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.package-btn--disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.current-info {
  padding: 12px 28px 20px;
  text-align: center;
  font-size: 13px;
  color: #64748b;
}

.selected-summary {
  display: block;
  margin-top: 6px;
  font-size: 14px;
  color: #1e40af;
}

.package-card--selected {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.duration-picker {
  margin-bottom: 16px;
}

.duration-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 8px;
  text-align: left;
}

.duration-options {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.duration-btn {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 13px;
}

.duration-btn:hover {
  border-color: #93c5fd;
  background: #eff6ff;
}

.duration-btn--active {
  border-color: #3b82f6;
  background: #dbeafe;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

.duration-days {
  font-weight: 600;
  color: #334155;
}

.duration-price {
  font-weight: 700;
  color: #1e40af;
}

.no-pricing {
  padding: 16px;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
  background: #f8fafc;
  border-radius: 10px;
  margin-bottom: 16px;
}

/* Transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
