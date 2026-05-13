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
              v-for="pkg in displayPackages"
              :key="pkg.id"
              :class="[
                'package-card',
                `package-card--${pkg.slug}`,
                { 'package-card--current': isCurrent(pkg) },
                { 'package-card--selected': selectedPkg?.id === pkg.id },
              ]"
            >
              <!-- Package Header Badge -->
              <div class="pkg-header" :class="`pkg-header--${pkg.slug}`">
                <span v-if="slugIcon(pkg.slug)" class="pkg-icon">{{ slugIcon(pkg.slug) }}</span>
                <span class="pkg-badge-label" :class="`pkg-badge-label--${pkg.slug}`">{{ slugLabel(pkg.slug) }}</span>
              </div>

              <!-- Daily Price -->
              <div class="pkg-daily-price">
                <span class="pkg-price-value">{{ pkg.slug === 'free' ? 'Miễn phí' : formatPrice(pkg.price) + 'đ' }}</span>
                <span v-if="pkg.slug !== 'free'" class="pkg-price-unit">/ ngày</span>
              </div>

              <!-- Push Price -->
              <div class="pkg-push-row">
                <span class="pkg-push-label">Đẩy tin (lần)</span>
                <span class="pkg-push-value">{{ getPushPrice(pkg) }}</span>
              </div>

              <!-- Duration Pricings -->
              <div class="pkg-durations">
                <div
                  v-for="pricing in pkg.pricings"
                  :key="pricing.id"
                  :class="['pkg-duration-row', { 'pkg-duration-row--active': selectedPricing?.id === pricing.id && selectedPkg?.id === pkg.id }]"
                  @click="selectPricing(pkg, pricing)"
                >
                  <span class="pkg-duration-label">{{ pricing.label }}</span>
                  <span class="pkg-duration-price">{{ formatPrice(pricing.price) }}đ</span>
                </div>
                <div v-if="pkg.slug === 'free' && (!pkg.pricings || pkg.pricings.length === 0)" class="pkg-free-durations">
                  <div class="pkg-duration-row pkg-duration-row--free">
                    <span class="pkg-duration-label">Gói 7 ngày</span>
                    <span class="pkg-duration-price pkg-duration-price--free">Miễn phí</span>
                  </div>
                  <div class="pkg-duration-row pkg-duration-row--free">
                    <span class="pkg-duration-label">Gói 15 ngày</span>
                    <span class="pkg-duration-price pkg-duration-price--free">Miễn phí</span>
                  </div>
                  <div class="pkg-duration-row pkg-duration-row--free">
                    <span class="pkg-duration-label">Gói 30 ngày</span>
                    <span class="pkg-duration-price pkg-duration-price--free">Miễn phí</span>
                  </div>
                  <div class="pkg-duration-row pkg-duration-row--free">
                    <span class="pkg-duration-label">Gói 90 ngày</span>
                    <span class="pkg-duration-price pkg-duration-price--free">Miễn phí</span>
                  </div>
                </div>
              </div>

              <!-- Features -->
              <ul class="pkg-features">
                <li v-for="(feat, idx) in getFeatures(pkg)" :key="idx" :class="feat.enabled ? 'feat-enabled' : 'feat-disabled'">
                  <span class="feat-icon">{{ feat.enabled ? '✅' : '❌' }}</span>
                  <span v-html="feat.text"></span>
                </li>
              </ul>

              <!-- Action -->
              <button
                v-if="pkg.slug !== 'free' && (canUpgrade(pkg) || isCurrent(pkg))"
                :class="['pkg-action-btn', `pkg-action-btn--${pkg.slug}`, selectedPkg?.id === pkg.id && selectedPricing ? 'pkg-action-btn--ready' : '']"
                :disabled="upgrading || !(selectedPkg?.id === pkg.id && selectedPricing)"
                @click="doUpgrade(pkg)"
              >
                <template v-if="upgrading && upgradingPkgId === pkg.id">
                  <div class="btn-spinner"></div> Đang xử lý...
                </template>
                <template v-else-if="isCurrent(pkg)">
                  🔄 Gia hạn {{ slugLabel(pkg.slug) }}
                </template>
                <template v-else>
                  Nâng cấp lên {{ slugLabel(pkg.slug) }}
                </template>
              </button>
              <button
                v-else-if="pkg.slug !== 'free'"
                class="pkg-action-btn pkg-action-btn--disabled"
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

// Only show: electron, ruby, gold, free (exclude silver/bac)
const displayPackages = computed(() => {
  const allowedSlugs = ['electron', 'ruby', 'gold', 'free'];
  const slugOrder = { electron: 0, ruby: 1, gold: 2, free: 3 };
  return [...packages.value]
    .filter(p => allowedSlugs.includes(p.slug))
    .sort((a, b) => (slugOrder[a.slug] ?? 99) - (slugOrder[b.slug] ?? 99));
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

function slugLabel(slug) {
  const map = { electron: 'Electron', ruby: 'Ruby', gold: 'Vàng', free: 'Tin thường' };
  return map[slug] || slug;
}

function slugIcon(slug) {
  const map = { electron: '⚡', ruby: '💎', gold: '🏅', free: '📋' };
  return map[slug] || null;
}

function getPushPrice(pkg) {
  const map = { electron: 'Miễn phí', ruby: '8.000đ', gold: '5.000đ', free: '1.000đ' };
  return map[pkg.slug] || 'N/A';
}

function getFeatures(pkg) {
  const featMap = {
    electron: [
      { text: 'Gói <strong>DUY NHẤT</strong> tích hợp 3D', enabled: true },
      { text: '<strong>TOP 1</strong> phủ sóng trên hệ sinh thái', enabled: true },
      { text: 'Giao diện hiển thị chuyên biệt', enabled: true },
      { text: 'Tự động đẩy tin <strong>MIỄN PHÍ</strong>', enabled: true },
    ],
    ruby: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Nhận tag <strong>Ruby</strong>', enabled: true },
      { text: 'Người xem ước tính <strong>X10</strong>', enabled: true },
      { text: 'Kích thước lớn, tăng lượt xem', enabled: true },
      { text: '<strong>TOP 2</strong> hiển thị trong danh sách', enabled: true },
    ],
    gold: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Nhận tag <strong>Vàng</strong>', enabled: true },
      { text: 'Người xem ước tính <strong>X6</strong>', enabled: true },
      { text: '<strong>TOP 3</strong> hiển thị trong danh sách', enabled: true },
    ],
    free: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Miễn phí hiển thị trong danh sách', enabled: true },
      { text: 'Lượt tiếp cận tự nhiên', enabled: true },
    ],
  };
  return featMap[pkg.slug] || [];
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
  max-width: 1100px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18);
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

/* ─── PACKAGES GRID ─── */
.packages-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
  padding: 0;
}

@media (max-width: 900px) {
  .packages-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 540px) {
  .packages-grid { grid-template-columns: 1fr; }
}

/* ─── PACKAGE CARD ─── */
.package-card {
  position: relative;
  border: 1px solid #e8ecf0;
  padding: 0;
  text-align: left;
  transition: all 0.3s ease;
  background: #fff;
  display: flex;
  flex-direction: column;
}

.package-card:first-child {
  border-radius: 0 0 0 0;
}

.package-card:hover {
  z-index: 2;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.package-card--selected {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  z-index: 3;
}

.package-card--current {
  background: #fafcff;
}

/* ─── Package Header ─── */
.pkg-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 14px 16px 10px;
}

.pkg-icon {
  font-size: 16px;
}

.pkg-badge-label {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 4px;
  letter-spacing: 0.3px;
}

/* Electron - dark navy */
.pkg-badge-label--electron {
  background: #1e293b;
  color: #fff;
}

/* Ruby - red gradient */
.pkg-badge-label--ruby {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #fff;
}

/* Gold - amber */
.pkg-badge-label--gold {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
}

/* Free - light gray */
.pkg-badge-label--free {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

/* ─── Daily Price ─── */
.pkg-daily-price {
  padding: 4px 16px 12px;
  border-bottom: 1px solid #f1f5f9;
}

.pkg-price-value {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.5px;
}

.pkg-price-unit {
  font-size: 14px;
  color: #94a3b8;
  margin-left: 4px;
}

/* ─── Push Price Row ─── */
.pkg-push-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
}

.pkg-push-label {
  color: #64748b;
}

.pkg-push-value {
  font-weight: 600;
  color: #334155;
}

/* ─── Duration Pricings ─── */
.pkg-durations {
  padding: 8px 16px 12px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.pkg-duration-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 13px;
}

.pkg-duration-row:hover {
  background: #f8fafc;
}

.pkg-duration-row--active {
  background: #eff6ff;
  border: 1.5px solid #3b82f6;
}

.pkg-duration-row--free {
  cursor: default;
}

.pkg-duration-row--free:hover {
  background: transparent;
}

.pkg-duration-label {
  color: #475569;
  font-weight: 500;
}

.pkg-duration-price {
  font-weight: 700;
  color: #1e40af;
}

.pkg-duration-price--free {
  color: #16a34a;
  font-weight: 600;
}

.pkg-free-durations {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* ─── Features ─── */
.pkg-features {
  list-style: none;
  padding: 12px 16px;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.pkg-features li {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 12px;
  line-height: 1.5;
}

.feat-icon {
  flex-shrink: 0;
  font-size: 11px;
  margin-top: 1px;
}

.feat-enabled {
  color: #334155;
}

.feat-disabled {
  color: #94a3b8;
}

/* ─── Action Button ─── */
.pkg-action-btn {
  margin: 0 16px 16px;
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #f1f5f9;
  color: #94a3b8;
}

.pkg-action-btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.pkg-action-btn--ready {
  color: #fff !important;
}

.pkg-action-btn--ready.pkg-action-btn--electron {
  background: linear-gradient(135deg, #1e293b, #334155) !important;
}

.pkg-action-btn--ready.pkg-action-btn--ruby {
  background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
}

.pkg-action-btn--ready.pkg-action-btn--gold {
  background: linear-gradient(135deg, #f59e0b, #d97706) !important;
}

.pkg-action-btn--ready:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.pkg-action-btn--disabled {
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

/* ─── Current Info ─── */
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

/* ─── Card Slug Borders ─── */
.package-card--electron {
  border-top: 3px solid #1e293b;
}

.package-card--ruby {
  border-top: 3px solid #dc2626;
}

.package-card--gold {
  border-top: 3px solid #f59e0b;
}

.package-card--free {
  border-top: 3px solid #e2e8f0;
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
