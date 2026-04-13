<template>
  <main class="min-h-screen bg-[#f4f8fc] pb-14 pt-24">
    <div class="mx-auto w-full max-w-[1240px] px-4 lg:px-6">
      <p class="text-xs text-slate-500">Trang chủ &gt; Đăng tin</p>
      <h1 class="mt-2 text-[24px] font-extrabold tracking-tight text-slate-900">Đăng tin bất động sản</h1>

      <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,760px)_330px] lg:justify-center">
      <form class="space-y-4 lg:w-[760px]" @submit.prevent="submitListing">
        <section class="section-card">
          <header class="section-title">
            <img :src="uploadImageIcon" alt="upload" class="h-5 w-5" />
            <h2>Hình ảnh, Video<span class="text-red-500">*</span></h2>
          </header>
          <p class="section-subtitle"><img :src="uploadImageIcon" alt="upload" class="inline h-3.5 w-3.5 align-[-2px]" /> Tải ảnh và video từ máy tính</p>

          <label class="upload-box mt-3">
            <img :src="plusImageIcon" alt="choose" class="mx-auto h-12 w-12 opacity-70" />
            <p class="mt-2 text-sm text-slate-600">Kéo thả tối thiểu 1 ảnh vào đây hoặc</p>
            <span class="upload-pill mt-2">Chọn tệp ảnh</span>
            <input class="hidden" type="file" multiple accept="image/*" @change="onImagesChange" />
          </label>
          <ul class="mt-2 list-disc pl-4 text-[11px] text-slate-500">
            <li>Hỗ trợ jpg, jpeg, png. Tối đa 10 ảnh.</li>
            <li>Kích thước mỗi ảnh tối đa 30MB, video tối đa 100MB.</li>
          </ul>

          <label class="mt-3 block">
            <span class="text-xs font-semibold text-slate-700">Video mp4 (tùy chọn)</span>
            <input class="input mt-1" type="file" accept="video/mp4" @change="onVideoChange" />
          </label>

          <p class="mt-2 text-xs text-slate-500">Đã chọn {{ form.images.length }} ảnh</p>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="homeImageIcon" alt="info" class="h-5 w-5" />
            <h2>Thông tin bất động sản</h2>
          </header>

          <div class="mt-3">
            <p class="field-label">Nhu cầu của bạn *</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button type="button" :class="pillClass(form.demandType === 'SALE')" @click="form.demandType = 'SALE'">Mua bán</button>
              <button type="button" :class="pillClass(form.demandType === 'RENT')" @click="form.demandType = 'RENT'">Cho thuê</button>
            </div>
          </div>

          <label class="mt-3 block">
            <span class="field-label">Tiêu đề *</span>
            <input v-model="form.title" class="input mt-1" maxlength="120" placeholder="Nhập tiêu đề" />
          </label>

          <label class="mt-3 block">
            <span class="field-label">Mô tả *</span>
            <textarea v-model="form.description" class="input mt-1 min-h-[120px]" maxlength="5000" placeholder="Mô tả chi tiết, ít nhất 20 ký tự"></textarea>
          </label>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Loại nhà đất *</span>
              <input v-model="form.propertyType" class="input mt-1" placeholder="APARTMENT, HOUSE..." />
            </label>
            <label>
              <span class="field-label">Giấy tờ pháp lý</span>
              <input v-model="form.furnitureStatus" class="input mt-1" placeholder="NONE / BASIC / FULL" />
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Dự án</span>
              <input v-model="form.projectName" class="input mt-1" placeholder="Nhập tên dự án" />
            </label>
            <label>
              <span class="field-label">Địa chỉ cụ thể</span>
              <input v-model="form.addressDetail" class="input mt-1" placeholder="Số nhà, tên đường" />
            </label>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="locationImageIcon" alt="location" class="h-5 w-5" />
            <h2>Vị trí</h2>
          </header>

          <label class="mt-3 block">
            <span class="field-label">Tìm kiếm địa chỉ bất động sản *</span>
            <input class="input mt-1" placeholder="Nhập địa chỉ" />
          </label>

          <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-8 text-center text-xs text-slate-500">
            Vị trí trên bản đồ
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Tỉnh / Thành phố *</span>
              <input v-model="form.provinceCode" class="input mt-1" placeholder="Mã tỉnh, ví dụ 01" />
            </label>
            <label>
              <span class="field-label">Quận / Huyện *</span>
              <input v-model="form.districtCode" class="input mt-1" placeholder="Mã quận" />
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Phường / Xã</span>
              <input v-model="form.wardCode" class="input mt-1" placeholder="Mã phường" />
            </label>
            <label>
              <span class="field-label">Đường / Phố</span>
              <input v-model="form.streetCode" class="input mt-1" placeholder="Mã đường" />
            </label>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="cameraImageIcon" alt="detail" class="h-5 w-5" />
            <h2>Chi tiết bất động sản</h2>
          </header>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Số phòng ngủ</span>
              <input v-model="form.bedrooms" class="input mt-1" type="number" min="0" />
            </label>
            <label>
              <span class="field-label">Số phòng tắm</span>
              <input v-model="form.bathrooms" class="input mt-1" type="number" min="0" />
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Diện tích *</span>
              <input v-model="form.area" class="input mt-1" type="number" min="0" step="0.1" placeholder="m2" />
            </label>
            <label>
              <span class="field-label">Giá bán / thuê</span>
              <input v-model="form.price" class="input mt-1 disabled:bg-slate-100" :disabled="form.isNegotiable" type="number" min="0" placeholder="VNĐ" />
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Mặt tiền (m)</span>
              <input v-model="form.facadeWidth" class="input mt-1" type="number" min="0" step="0.1" />
            </label>
            <label>
              <span class="field-label">Đường vào (m)</span>
              <input v-model="form.roadWidth" class="input mt-1" type="number" min="0" step="0.1" />
            </label>
          </div>

          <div class="mt-3 flex items-center gap-2">
            <input id="is-negotiable" v-model="form.isNegotiable" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
            <label for="is-negotiable" class="text-sm text-slate-700">Có thương lượng</label>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="informationImageIcon" alt="contact" class="h-5 w-5" />
            <h2>Thông tin liên hệ</h2>
          </header>

          <div class="mt-3 flex gap-2">
            <button type="button" :class="pillClass(form.posterType === 'OWNER')" @click="form.posterType = 'OWNER'">Chủ nhà</button>
            <button type="button" :class="pillClass(form.posterType === 'BROKER')" @click="form.posterType = 'BROKER'">Môi giới</button>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Họ và tên *</span>
              <input v-model="form.contactName" class="input mt-1" placeholder="Nhập họ tên" />
            </label>
            <label>
              <span class="field-label">Số điện thoại *</span>
              <input v-model="form.contactPhone" class="input mt-1" placeholder="VD: 0912345678" />
            </label>
          </div>

          <label class="mt-3 block">
            <span class="field-label">Email</span>
            <input v-model="form.contactEmail" class="input mt-1" type="email" placeholder="vd_email@example.com" />
          </label>
        </section>

        <section class="section-card">
          <header class="section-title collapsible-title">
            <img :src="cameraImageIcon" alt="schedule" class="h-5 w-5" />
            <h2>Đặt lịch xem nhà</h2>
            <span class="chevron">⌃</span>
          </header>
          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Thời gian *</span>
              <input v-model="form.appointmentAt" class="input mt-1" type="datetime-local" />
            </label>
            <label>
              <span class="field-label">Số điện thoại liên hệ</span>
              <input v-model="form.appointmentContactPhone" class="input mt-1" />
            </label>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title collapsible-title">
            <img :src="documentImageIcon" alt="verify" class="h-5 w-5" />
            <h2>Xác thực bất động sản</h2>
            <span class="chevron">⌃</span>
          </header>

          <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <p class="font-semibold">Xác thực bất động sản tại MewayLand</p>
            <p class="mt-1 text-xs">Khi hoàn thành, tin đăng sẽ được ưu tiên hiển thị và tăng độ tin cậy.</p>
          </div>

          <div class="mt-3 flex items-center gap-2">
            <input id="request-verification" v-model="form.requestVerification" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
            <label for="request-verification" class="text-sm text-slate-700">CCCD/CMND chủ sở hữu</label>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-3">
            <label class="file-box">
              <div class="file-box-inner">
                <img :src="plusImageIcon" alt="plus" class="h-6 w-6 opacity-70" />
                <span class="text-xs text-slate-500">Mặt trước</span>
              </div>
              <input class="hidden" type="file" accept="image/*" @change="onFrontCardChange" />
            </label>
            <label class="file-box">
              <div class="file-box-inner">
                <img :src="plusImageIcon" alt="plus" class="h-6 w-6 opacity-70" />
                <span class="text-xs text-slate-500">Mặt sau</span>
              </div>
              <input class="hidden" type="file" accept="image/*" @change="onBackCardChange" />
            </label>
          </div>

          <label class="upload-box mt-3">
            <img :src="chooseImageIcon" alt="document" class="mx-auto h-10 w-10 opacity-75" />
            <p class="mt-2 text-xs text-slate-600">Tải lên ảnh chụp trước/sau của giấy tờ pháp lý</p>
            <span class="upload-pill mt-2">Chọn tệp ảnh</span>
            <input class="hidden" type="file" multiple accept="image/*" @change="onLegalDocumentsChange" />
          </label>
        </section>

        <section v-if="submitError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {{ submitError }}
        </section>

        <section v-if="Object.keys(validationErrors).length" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
          <p class="mb-2 text-sm font-semibold text-amber-900">Lỗi validate từ API</p>
          <ul class="list-disc space-y-1 pl-5 text-sm text-amber-800">
            <li v-for="(messages, field) in validationErrors" :key="field">
              {{ field }}: {{ Array.isArray(messages) ? messages.join(', ') : messages }}
            </li>
          </ul>
        </section>

        <div class="sticky bottom-4 z-20 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
          <div class="flex gap-3">
            <button type="button" class="flex-1 rounded-xl border border-sky-300 px-4 py-2.5 text-sm font-semibold text-sky-600" @click="resetForm">Xem trước tin đăng</button>
            <button type="submit" :disabled="loading" class="flex-1 rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60">
              {{ loading ? 'Đang đăng tin...' : 'Tiếp tục' }}
            </button>
          </div>
        </div>
      </form>

      <aside class="hidden lg:block">
        <div class="sticky top-24 space-y-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
              <span class="step-dot active">1</span>
              <span>Điền thông tin</span>
              <span class="mx-2 h-px flex-1 bg-slate-200"></span>
              <span class="step-dot">2</span>
              <span class="text-slate-400">Cấu hình tin đăng</span>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-[28px] font-extrabold leading-none text-slate-300">{{ totalScore.toFixed(1) }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-800">Thông tin ở mức tối thiểu</p>
            <p class="text-sm text-slate-400">Chưa tiếp cận được khách hàng</p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="text-[28px] font-extrabold leading-none text-slate-900">Chi tiết điểm thông tin</h3>

            <div class="mt-4 space-y-3">
              <div class="score-row">
                <div class="score-label">
                  <span class="circle" :class="{ done: mediaDone }"></span>
                  <span>Hình ảnh và video</span>
                </div>
                <p class="score-value">{{ mediaDone ? '4/4đ' : '0/4đ' }}</p>
              </div>
              <p class="score-sub">Hoàn thiện {{ mediaDone ? '100%' : '0%' }}</p>

              <div class="score-row">
                <div class="score-label">
                  <span class="circle" :class="{ done: infoDone }"></span>
                  <span>Thông tin BĐS</span>
                </div>
                <p class="score-value">{{ infoPoints }}/2đ</p>
              </div>
              <p class="score-sub">Hoàn thiện {{ infoPercent }}%</p>

              <div class="score-row">
                <div class="score-label">
                  <span class="circle" :class="{ done: detailDone }"></span>
                  <span>Chi tiết BĐS</span>
                </div>
                <p class="score-value">{{ detailPoints }}/3đ</p>
              </div>
              <p class="score-sub">Hoàn thiện {{ detailPercent }}%</p>

              <div class="score-row">
                <div class="score-label">
                  <span class="circle done"></span>
                  <span>Thông tin liên hệ</span>
                </div>
                <p class="score-value text-emerald-600">1/1đ</p>
              </div>
              <p class="score-sub">Hoàn thiện 100%</p>
            </div>
          </div>
        </div>
      </aside>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import listingService from "@/services/listingService";
import uploadImageIcon from "@/assets/images/listing/postlisting/uploadImage.png";
import locationImageIcon from "@/assets/images/listing/postlisting/locationImage.png";
import informationImageIcon from "@/assets/images/listing/postlisting/information.png";
import homeImageIcon from "@/assets/images/listing/postlisting/homeImage.png";
import documentImageIcon from "@/assets/images/listing/postlisting/document.png";
import chooseImageIcon from "@/assets/images/listing/postlisting/chooseImage.png";
import cameraImageIcon from "@/assets/images/listing/postlisting/camera.png";
import plusImageIcon from "@/assets/images/listing/postlisting/PlusImage.png";

function createInitialState() {
  return {
    demandType: "SALE",
    title: "",
    description: "",
    propertyType: "APARTMENT",
    provinceCode: "",
    districtCode: "",
    wardCode: "",
    streetCode: "",
    projectName: "",
    addressDetail: "",
    area: "",
    price: "",
    isNegotiable: false,
    bedrooms: "",
    bathrooms: "",
    floors: "",
    floorNumber: "",
    balconies: "",
    facadeWidth: "",
    depth: "",
    roadWidth: "",
    directionCode: "",
    balconyDirectionCode: "",
    furnitureStatus: "",
    contactName: "",
    contactPhone: "",
    contactEmail: "",
    posterType: "OWNER",
    lat: "",
    lng: "",
    packageId: "",
    images: [],
    video: null,
    attributeIds: [],
    requestVerification: false,
    identityCardFront: null,
    identityCardBack: null,
    legalDocuments: [],
    appointmentAt: "",
    appointmentContactName: "",
    appointmentContactPhone: "",
    appointmentContactEmail: "",
    appointmentNote: "",
  };
}

const form = reactive(createInitialState());
const loading = ref(false);
const submitError = ref("");
const validationErrors = ref({});

const mediaDone = computed(() => form.images.length > 0);
const infoFieldCount = computed(() => {
  let count = 0;
  if (form.title.trim()) count++;
  if (form.description.trim().length >= 20) count++;
  if (form.propertyType.trim()) count++;
  if (form.provinceCode.trim()) count++;
  if (form.districtCode.trim()) count++;
  return count;
});
const infoPercent = computed(() => Math.round((infoFieldCount.value / 5) * 100));
const infoPoints = computed(() => (infoPercent.value >= 80 ? 2 : infoPercent.value >= 40 ? 1 : 0));
const infoDone = computed(() => infoPoints.value === 2);

const detailFieldCount = computed(() => {
  let count = 0;
  if (Number(form.area) > 0) count++;
  if (form.isNegotiable || Number(form.price) > 0) count++;
  if (Number(form.bedrooms) >= 0 && Number(form.bathrooms) >= 0 && (form.bedrooms !== "" || form.bathrooms !== "")) count++;
  return count;
});
const detailPercent = computed(() => Math.round((detailFieldCount.value / 3) * 100));
const detailPoints = computed(() => detailFieldCount.value);
const detailDone = computed(() => detailFieldCount.value === 3);

const totalScore = computed(() => {
  const media = mediaDone.value ? 4 : 0;
  return media + infoPoints.value + detailPoints.value + 1;
});

function onImagesChange(event) {
  const files = event.target.files ? Array.from(event.target.files) : [];
  form.images = files;
}

function onVideoChange(event) {
  form.video = event.target.files?.[0] || null;
}

function onFrontCardChange(event) {
  form.identityCardFront = event.target.files?.[0] || null;
}

function onBackCardChange(event) {
  form.identityCardBack = event.target.files?.[0] || null;
}

function onLegalDocumentsChange(event) {
  const files = event.target.files ? Array.from(event.target.files) : [];
  form.legalDocuments = files;
}

function pillClass(active) {
  return active
    ? "rounded-full bg-sky-500 px-4 py-1.5 text-xs font-semibold text-white"
    : "rounded-full bg-slate-100 px-4 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200";
}

function resetForm() {
  Object.assign(form, createInitialState());
  submitError.value = "";
  validationErrors.value = {};
}

async function submitListing() {
  loading.value = true;
  submitError.value = "";
  validationErrors.value = {};

  try {
    const response = await listingService.create(form);
    submitError.value = "";
    alert(response.data?.message || "Đăng tin thành công");
    resetForm();
  } catch (error) {
    const data = error.response?.data;
    validationErrors.value = data?.errors || {};
    submitError.value = data?.message || "Gọi API thất bại. Kiểm tra token và dữ liệu.";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.section-card {
  border: 1px solid #e6edf5;
  border-radius: 14px;
  background: #fff;
  padding: 14px;

.file-box-inner {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.collapsible-title {
  justify-content: space-between;
}

.collapsible-title img {
  margin-right: 8px;
}

.collapsible-title h2 {
  margin-right: auto;
}

.chevron {
  font-size: 14px;
  color: #475569;
}

.section-title h2 {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.section-subtitle {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
}

.field-label {
  font-size: 11px;
  font-weight: 600;
  color: #334155;
}

.input {
  width: 100%;
  border: 1px solid #e1eaf4;
  border-radius: 8px;
  background: #f3f7fc;
  padding: 8px 10px;
  font-size: 12px;
  color: #0f172a;
  outline: none;
}

.input:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
  background: #fff;
}

.upload-box {
  display: block;
  border: 1px dashed #96c8ef;
  border-radius: 10px;
  background: #f8fcff;
  padding: 16px 10px;
  text-align: center;
}

.upload-pill {
  display: inline-block;
  border-radius: 999px;
  border: 1px solid #8fd0f7;
  color: #0284c7;
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 600;
  background: #fff;
}

.file-box {
  display: flex;
  min-height: 96px;
  align-items: center;
  justify-content: center;
  border: 1px dashed #cdd8e6;
  border-radius: 12px;
  background: #f8fbff;
}

.step-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 999px;
  font-size: 11px;
  background: #efe9ff;
  color: #8b78c6;
}

.step-dot.active {
  background: #2563eb;
  color: #fff;
}

.score-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.score-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #1f2937;
  font-weight: 500;
}

.score-value {
  font-size: 20px;
  font-weight: 700;
  color: #334155;
}

.score-sub {
  margin-top: -8px;
  font-size: 12px;
  color: #94a3b8;
}

.circle {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid #3b82f6;
}

.circle.done {
  border-color: #16a34a;
  background: #16a34a;
}
</style>
