<template>
  <main class="min-h-screen bg-[#f4f8fc] pb-14 pt-24">
    <div class="toast-stack">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['toast-item', `toast-${toast.type}`]"
      >
        {{ toast.message }}
      </div>
    </div>

    <div class="mx-auto w-full max-w-[1240px] px-4 lg:px-6">
      <p class="text-xs text-slate-500">
        <router-link to="/" class="hover:text-sky-600 hover:underline">Trang chủ</router-link>
        <span> &gt; </span>
        <span>{{ isEditMode ? 'Chỉnh sửa tin' : 'Đăng tin' }}</span>
      </p>
      <h1 class="mt-2 text-[24px] font-extrabold tracking-tight text-slate-900">{{ isEditMode ? 'Chỉnh sửa tin đăng' : 'Đăng tin bất động sản' }}</h1>

      <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,760px)_330px] lg:justify-center">
      <form class="space-y-4 lg:w-[760px]" @submit.prevent="submitListing">
        <section class="section-card">
          <header class="section-title">
            <img :src="uploadImageIcon" alt="upload" class="h-5 w-5" />
            <h2>Hình ảnh, Video</h2>
          </header>
          <p class="section-subtitle"><img :src="uploadImageIcon" alt="upload" class="inline h-3.5 w-3.5 align-[-2px]" /> Tải ảnh và video từ máy tính</p>

          <label :class="['upload-box mt-3', fieldError('images') && 'input-error']">
            <img :src="plusImageIcon" alt="choose" class="mx-auto h-12 w-12 opacity-70" />
            <p class="mt-2 text-sm text-slate-600">Kéo thả tối thiểu 1 ảnh vào đây hoặc</p>
            <span class="upload-pill mt-2">Chọn tệp ảnh</span>
            <input class="hidden" type="file" multiple accept="image/*" @change="onImagesChange" />
          </label>
          <p v-if="fieldError('images')" class="field-error mt-2">{{ fieldErrorMessage('images') }}</p>
          <p v-if="imageUploadError" class="field-error mt-2">{{ imageUploadError }}</p>
          <ul class="mt-2 list-disc pl-4 text-[11px] text-slate-500">
            <li>Hỗ trợ jpg, jpeg, png. Tối đa 10 ảnh.</li>
            <li>Kích thước mỗi ảnh tối đa 30MB, video tối đa 100MB.</li>
          </ul>

          <div v-if="imagePreviews.length" class="mt-3">
            <p class="text-xs font-semibold text-slate-700">Ảnh đã chọn ({{ imagePreviews.length }})</p>
            <div class="preview-grid mt-2">
              <figure v-for="(preview, idx) in imagePreviews" :key="preview.url" class="preview-card group">
                <img :src="preview.url" :alt="preview.name" class="preview-image" />
                <button
                  type="button"
                  class="absolute right-1 top-1 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-600"
                  title="Xóa ảnh"
                  @click="removeImage(idx)"
                >✕</button>
                <figcaption class="preview-name" :title="preview.name">{{ preview.name }}</figcaption>
              </figure>
            </div>
          </div>

          <div class="mt-4">
            <p class="text-xs font-semibold text-slate-700">Video (tối đa 1 video MP4)</p>
            <label class="upload-box mt-2">
              <img :src="plusImageIcon" alt="choose-video" class="mx-auto h-12 w-12 opacity-70" />
              <p class="mt-2 text-sm text-slate-600">Tải video MP4 từ máy tính</p>
              <span class="upload-pill mt-2">Chọn tệp video</span>
              <input class="hidden" type="file" accept="video/mp4" @change="onVideoChange" />
            </label>
            <p v-if="videoUploadError" class="field-error mt-2">{{ videoUploadError }}</p>
            <div v-if="videoPreviewName" class="mt-2 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
              <span class="truncate">{{ videoPreviewName }}</span>
              <button type="button" class="rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-600" @click="removeVideo">Xóa video</button>
            </div>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="homeImageIcon" alt="info" class="h-5 w-5" />
            <h2>Thông tin bất động sản</h2>
          </header>

          <div class="mt-3">
            <p class="field-label required">Nhu cầu của bạn</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button type="button" :class="pillClass(form.demandType === 'SALE')" @click="form.demandType = 'SALE'">Mua bán</button>
              <button type="button" :class="pillClass(form.demandType === 'RENT')" @click="form.demandType = 'RENT'">Cho thuê</button>
            </div>
          </div>

          <label class="mt-3 block">
            <span class="field-label required">Tiêu đề</span>
            <input v-model="form.title" :class="['input mt-1', fieldError('title') && 'input-error']" maxlength="120" placeholder="Nhập tiêu đề" @blur="handleTextBlur('title')" />
            <p v-if="fieldError('title')" class="field-error">{{ fieldErrorMessage('title') }}</p>
            <p class="mt-1 text-right text-[12px] text-slate-400">{{ titleCount }}/120 ký tự</p>
          </label>

          <div class="tip-box">
            <p class="flex items-center gap-2 text-[13px] font-semibold text-slate-700">
              <img :src="infoDotIcon" alt="info" class="h-4 w-4" />
              Cấu trúc tiêu đề nên có
            </p>
            <p class="mt-1 text-[13px] text-slate-500">Loại căn hộ + Diện tích + Số PN + Vị trí</p>
            <p class="mt-1 text-[13px] text-sky-500">VD: Căn bán căn hộ chung cư 80m2, 2PN, Vinhomes Smart City, Hà Nội</p>
          </div>

          <label class="mt-3 block">
            <span class="field-label required">Mô tả</span>
            <textarea v-model="form.description" :class="['input mt-1 min-h-[140px]', fieldError('description') && 'input-error']" maxlength="5000" placeholder="VD: Giới thiệu các đặc điểm nổi bật của bất động sản:&#10;- Các tiện ích xung quanh: gần công viên, gần trường học&#10;- Thời gian đến khu vực trung tâm, tiện ích xung quanh" @blur="handleTextBlur('description', true)"></textarea>
            <p v-if="fieldError('description')" class="field-error">{{ fieldErrorMessage('description') }}</p>
            <p class="mt-1 text-right text-[12px] text-slate-400">{{ descriptionCount }}/5000</p>
          </label>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label required">Loại nhà đất</span>
              <select v-model="form.propertyType" :class="['input mt-1', fieldError('propertyType') && 'input-error']" @blur="touchField('propertyType')">
                <option v-for="option in currentPropertyTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <p v-if="fieldError('propertyType')" class="field-error">{{ fieldErrorMessage('propertyType') }}</p>
            </label>
            <label>
              <span class="field-label">Giấy tờ pháp lý</span>
              <button type="button" class="input mt-1 legal-trigger" @click="toggleLegalDropdown" ref="legalTriggerRef">
                <span v-if="!form.legalPaperTypes.length" class="text-slate-400">Chọn giấy tờ pháp lý</span>
                <span v-else class="legal-selected-text">{{ selectedLegalPaperLabels }}</span>
                <span class="dropdown-arrow-icon" aria-hidden="true"></span>
              </button>
              <div v-if="showLegalDropdown" class="legal-dropdown mt-2" ref="legalDropdownRef">
                <label v-for="option in legalPaperOptions" :key="option.value" class="legal-option">
                  <span>{{ option.label }}</span>
                  <input type="checkbox" :checked="form.legalPaperTypes.includes(option.value)" @change="toggleLegalPaper(option.value)" />
                </label>
              </div>
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-1">
            <label>
              <span class="field-label required">Diện tích (m2)</span>
              <input v-model="form.area" :class="['input mt-1', fieldError('area') && 'input-error']" type="text" inputmode="decimal" placeholder="Nhập số" @input="onNumberInput($event, 'area', true)" @blur="touchField('area')" />
              <p v-if="fieldError('area')" class="field-error">{{ fieldErrorMessage('area') }}</p>
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-1">
            <label>
              <span class="field-label required">{{ priceLabel }}</span>
              <input v-model="form.price" :class="['input mt-1 disabled:bg-slate-100', fieldError('price') && 'input-error']" :disabled="form.isNegotiable" type="text" inputmode="numeric" placeholder="Nhập số" @input="onNumberInput($event, 'price', false)" @blur="touchField('price')" />
              <p v-if="fieldError('price')" class="field-error">{{ fieldErrorMessage('price') }}</p>
            </label>
          </div>

          <div class="mt-3 flex items-center gap-2">
            <input id="is-negotiable-info" v-model="form.isNegotiable" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
            <label for="is-negotiable-info" class="text-[14px] text-slate-500">Giá Thương lượng</label>
          </div>

          <div v-if="form.demandType === 'RENT'" class="mt-3 space-y-3">
            <div>
              <p class="field-label">Thời gian thuê tối thiểu</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" :class="pillClass(form.rentMinTerm === '1_month')" @click="form.rentMinTerm = '1_month'">1 tháng</button>
                <button type="button" :class="pillClass(form.rentMinTerm === '3_months')" @click="form.rentMinTerm = '3_months'">3 tháng</button>
                <button type="button" :class="pillClass(form.rentMinTerm === '6_months')" @click="form.rentMinTerm = '6_months'">6 tháng</button>
                <button type="button" :class="pillClass(form.rentMinTerm === '1_year')" @click="form.rentMinTerm = '1_year'">1 năm</button>
              </div>
              <p v-if="fieldError('rentMinTerm')" class="field-error mt-2">{{ fieldErrorMessage('rentMinTerm') }}</p>
            </div>

            <div>
              <p class="field-label">Kỳ thanh toán</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" :class="pillClass(form.rentPaymentInterval === 'monthly')" @click="form.rentPaymentInterval = 'monthly'">1 tháng/lần</button>
                <button type="button" :class="pillClass(form.rentPaymentInterval === 'quarter')" @click="form.rentPaymentInterval = 'quarter'">3 tháng/lần</button>
                <button type="button" :class="pillClass(form.rentPaymentInterval === 'half_year')" @click="form.rentPaymentInterval = 'half_year'">6 tháng/lần</button>
                <button type="button" :class="pillClass(form.rentPaymentInterval === 'yearly')" @click="form.rentPaymentInterval = 'yearly'">1 năm/lần</button>
              </div>
              <p v-if="fieldError('rentPaymentInterval')" class="field-error mt-2">{{ fieldErrorMessage('rentPaymentInterval') }}</p>
            </div>

            <div>
              <p class="field-label">Đặt cọc</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" :class="pillClass(form.rentDeposit === 'none')" @click="form.rentDeposit = 'none'">Không</button>
                <button type="button" :class="pillClass(form.rentDeposit === '1_month')" @click="form.rentDeposit = '1_month'">1 tháng</button>
                <button type="button" :class="pillClass(form.rentDeposit === '3_months')" @click="form.rentDeposit = '3_months'">3 tháng</button>
                <button type="button" :class="pillClass(form.rentDeposit === '6_months')" @click="form.rentDeposit = '6_months'">6 tháng</button>
                <button type="button" :class="pillClass(form.rentDeposit === '1_year')" @click="form.rentDeposit = '1_year'">1 năm</button>
              </div>
            </div>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="locationImageIcon" alt="location" class="h-5 w-5" />
            <h2>Vị trí</h2>
          </header>

          <label class="mt-3 block">
            <span class="field-label required">Tìm kiếm địa chỉ bất động sản</span>
            <div class="mt-1 flex gap-2">
              <input
                v-model="locationSearchText"
                class="input"
                placeholder="Nhập dự án, địa chỉ"
                @keyup.enter.prevent="searchAddressOnMap"
              />
              <button type="button" class="search-btn" @click="searchAddressOnMap">{{ locationSearching ? '...' : 'Tìm' }}</button>
            </div>
          </label>

          <p class="mt-3 text-[14px] font-semibold text-slate-700">Vị trí trên bản đồ</p>
          <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
            <div ref="mapElement" class="location-map"></div>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label required">Tỉnh / Thành phố</span>
              <select v-model="form.provinceCode" :class="['input mt-1', fieldError('provinceCode') && 'input-error']" @change="touchField('provinceCode')">
                <option value="">Chọn Tỉnh/Thành phố</option>
                <option v-for="province in provinces" :key="province.code" :value="String(province.code)">
                  {{ province.name }}
                </option>
              </select>
              <p v-if="fieldError('provinceCode')" class="field-error">{{ fieldErrorMessage('provinceCode') }}</p>
            </label>
            <label>
              <span class="field-label required">Phường / Xã</span>
              <select
                v-model="form.wardCode"
                :class="['input mt-1', fieldError('wardCode') && 'input-error']"
                :disabled="!form.provinceCode || wardsLoading"
                @change="touchField('wardCode')"
                @blur="touchField('wardCode')"
              >
                <option value="">{{ wardsLoading ? 'Đang tải phường/xã...' : 'Chọn Phường/Xã' }}</option>
                <option v-for="ward in wards" :key="ward.code" :value="String(ward.code)">
                  {{ ward.name }}
                </option>
              </select>
              <p v-if="fieldError('wardCode')" class="field-error">{{ fieldErrorMessage('wardCode') }}</p>
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label required">Đường / Phố</span>
              <input
                v-model="form.streetCode"
                :class="['input mt-1', fieldError('streetCode') && 'input-error']"
                placeholder="Nhập đường/phố"
                @blur="handleTextBlur('streetCode')"
              />
              <p v-if="fieldError('streetCode')" class="field-error">{{ fieldErrorMessage('streetCode') }}</p>
            </label>
            <label>
              <span class="field-label required">Địa chỉ cụ thể</span>
              <input
                v-model="form.addressDetail"
                :class="['input mt-1', fieldError('addressDetail') && 'input-error']"
                inputmode="text"
                autocomplete="off"
                placeholder="Nhập địa chỉ"
                @blur="handleTextBlur('addressDetail')"
              />
              <p v-if="fieldError('addressDetail')" class="field-error">{{ fieldErrorMessage('addressDetail') }}</p>
            </label>
          </div>

          <p v-if="locationLoadError" class="mt-2 text-xs text-red-500">{{ locationLoadError }}</p>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="cameraImageIcon" alt="detail" class="h-5 w-5" />
            <h2>Chi tiết bất động sản</h2>
          </header>

          <div class="mt-3 grid gap-4 md:grid-cols-2">
            <div>
              <p class="field-label with-icon">
                <img :src="bedIcon" alt="bed" class="h-4 w-4" />
                <span>Số phòng ngủ</span>
              </p>
              <div class="quick-row mt-2">
                <button
                  v-for="n in quickNumberOptions"
                  :key="`bed-${n}`"
                  type="button"
                  :class="quickChipClass(Number(form.bedrooms) === n)"
                  @click="setQuickNumber('bedrooms', n)"
                >
                  {{ n }}
                </button>
                <input v-model="form.bedrooms" class="quick-input" type="text" inputmode="numeric" placeholder="Nhập số" @input="onNumberInput($event, 'bedrooms', false)" />
              </div>
            </div>

            <div>
              <p class="field-label with-icon">
                <img :src="bathIcon" alt="bath" class="h-4 w-4" />
                <span>Số phòng tắm</span>
              </p>
              <div class="quick-row mt-2">
                <button
                  v-for="n in quickNumberOptions"
                  :key="`bath-${n}`"
                  type="button"
                  :class="quickChipClass(Number(form.bathrooms) === n)"
                  @click="setQuickNumber('bathrooms', n)"
                >
                  {{ n }}
                </button>
                <input v-model="form.bathrooms" class="quick-input" type="text" inputmode="numeric" placeholder="Nhập số" @input="onNumberInput($event, 'bathrooms', false)" />
              </div>
            </div>
          </div>

          <div class="mt-3 grid gap-4 md:grid-cols-2">
            <label>
              <span class="field-label">Mặt tiền</span>
              <div class="unit-input mt-2">
                <input v-model="form.facadeWidth" class="input" type="text" inputmode="decimal" placeholder="Nhập số" @input="onNumberInput($event, 'facadeWidth', true)" />
                <span class="unit-label">m</span>
              </div>
            </label>
            <label>
              <span class="field-label">Chiều sâu</span>
              <div class="unit-input mt-2">
                <input v-model="form.depth" class="input" type="text" inputmode="decimal" placeholder="Nhập số" @input="onNumberInput($event, 'depth', true)" />
                <span class="unit-label">m</span>
              </div>
            </label>
          </div>

          <div class="mt-4">
            <button type="button" class="more-info-toggle" @click="showMoreDetail = !showMoreDetail">
              <span class="field-label">Thêm thông tin</span>
              <span class="more-info-badge">Điền đủ thông tin, tăng lượt tiếp cận</span>
              <span class="dropdown-arrow-icon" aria-hidden="true"></span>
            </button>
          </div>

          <div v-if="showMoreDetail" class="mt-3 space-y-3">
            <div class="grid gap-4 md:grid-cols-2">
              <label>
                <span class="field-label">Tầng thứ</span>
                <input v-model="form.floorNumber" class="input mt-2" type="text" inputmode="numeric" placeholder="Nhập số" @input="onNumberInput($event, 'floorNumber', false)" />
              </label>
              <label>
                <span class="field-label">Số tầng</span>
                <input v-model="form.floors" class="input mt-2" type="text" inputmode="numeric" placeholder="Nhập số" @input="onNumberInput($event, 'floors', false)" />
              </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <label>
                <span class="field-label">Hướng nhà / đất</span>
                <select v-model="form.directionCode" class="input mt-2">
                  <option value="">Không</option>
                  <option v-for="direction in directionOptions" :key="`house-${direction.value}`" :value="direction.value">
                    {{ direction.label }}
                  </option>
                </select>
              </label>
              <label>
                <span class="field-label">Hướng ban công</span>
                <select v-model="form.balconyDirectionCode" class="input mt-2">
                  <option value="">Không</option>
                  <option v-for="direction in directionOptions" :key="`balcony-${direction.value}`" :value="direction.value">
                    {{ direction.label }}
                  </option>
                </select>
              </label>
            </div>

            <div>
              <p class="field-label">Số ban công</p>
              <div class="quick-row mt-2">
                <button
                  v-for="n in quickNumberOptions"
                  :key="`balcony-${n}`"
                  type="button"
                  :class="quickChipClass(Number(form.balconies) === n)"
                  @click="setQuickNumber('balconies', n)"
                >
                  {{ n }}
                </button>
                <input v-model="form.balconies" class="quick-input" type="text" inputmode="numeric" placeholder="Nhập số" @input="onNumberInput($event, 'balconies', false)" />
              </div>
            </div>
          </div>

            <div class="mt-4">
            <p class="field-label">Nội thất</p>
            <div class="mt-2 flex items-center gap-2">
              <button type="button" :class="pillClass(form.furnitureStatus === 'FULL')" @click="setFurnitureStatus('FULL')">Có</button>
              <button type="button" :class="pillClass(form.furnitureStatus === 'NONE')" @click="setFurnitureStatus('NONE')">Không</button>
            </div>

            </div>

          <div class="mt-4">
            <p class="field-label">Tiện ích</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="amenity in amenityOptions"
                :key="amenity"
                type="button"
                class="amenity-chip"
                :class="{ active: selectedAmenities.includes(amenity) }"
                @click="toggleAmenity(amenity)"
              >
                <span>+</span>
                <span>{{ amenity }}</span>
              </button>
            </div>
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
              <span class="field-label required">Họ và tên</span>
              <input v-model="form.contactName" :class="['input mt-1', fieldError('contactName') && 'input-error']" placeholder="Nhập họ tên" @blur="handleTextBlur('contactName')" />
              <p v-if="fieldError('contactName')" class="field-error">{{ fieldErrorMessage('contactName') }}</p>
            </label>
            <label>
              <span class="field-label required">Số điện thoại</span>
              <input v-model="form.contactPhone" :class="['input mt-1', fieldError('contactPhone') && 'input-error']" type="tel" inputmode="numeric" maxlength="10" placeholder="VD: 0912345678" @input="onPhoneInput" @blur="touchField('contactPhone')" />
              <p v-if="fieldError('contactPhone')" class="field-error">{{ fieldErrorMessage('contactPhone') }}</p>
            </label>
          </div>

          <label class="mt-3 block">
            <span class="field-label required">Email</span>
            <input v-model="form.contactEmail" :class="['input mt-1', fieldError('contactEmail') && 'input-error']" type="email" placeholder="vd_email@gmail.com" @blur="handleTextBlur('contactEmail')" />
            <p v-if="fieldError('contactEmail')" class="field-error">{{ fieldErrorMessage('contactEmail') }}</p>
          </label>
        </section>
          <AppointmentSlotsForm ref="appointmentForm" />

        <section class="section-card">
          <button type="button" class="appointment-header" @click="showVerificationSection = !showVerificationSection">
            <span class="inline-flex items-center gap-2">
              <img :src="homeImageIcon" alt="verify" class="h-5 w-5" />
              <h2>Xác thực bất động sản</h2>
            </span>
            <span class="dropdown-arrow-icon" aria-hidden="true"></span>
          </button>

          <div v-if="showVerificationSection" class="mt-4 space-y-4">
            <div class="verify-note-box">
              <p class="text-lg tracking-wide">⭐ ⭐ ⭐</p>
              <p class="mt-1 text-[20px] font-semibold text-amber-900">Xác thực bất động sản tại Propify</p>
              <p class="mt-1 text-sm text-slate-600">Khi hoàn thành, tin đăng sẽ được ưu tiên vị trí hiển thị trên Propify</p>
            </div>

            <p class="verify-label-row">
              <span>👥 CCCD/CMND chủ sở hữu</span>
              <img :src="infoDotIcon" alt="info" class="h-4 w-4 opacity-70" />
            </p>

            <div class="grid grid-cols-2 gap-3">
              <label class="file-box">
                <div v-if="frontCardPreviewUrl" class="file-box-inner">
                  <img :src="frontCardPreviewUrl" alt="CCCD mặt trước" class="id-preview" />
                  <span class="text-xs text-slate-500">Mặt trước</span>
                </div>
                <div v-else class="file-box-inner">
                  <img :src="plusImageIcon" alt="plus" class="h-6 w-6 opacity-70" />
                  <span class="text-xs text-slate-500">Mặt trước</span>
                </div>
                <input class="hidden" type="file" accept="image/*" @change="onFrontCardChange" />
              </label>
              <label class="file-box">
                <div v-if="backCardPreviewUrl" class="file-box-inner">
                  <img :src="backCardPreviewUrl" alt="CCCD mặt sau" class="id-preview" />
                  <span class="text-xs text-slate-500">Mặt sau</span>
                </div>
                <div v-else class="file-box-inner">
                  <img :src="plusImageIcon" alt="plus" class="h-6 w-6 opacity-70" />
                  <span class="text-xs text-slate-500">Mặt sau</span>
                </div>
                <input class="hidden" type="file" accept="image/*" @change="onBackCardChange" />
              </label>
            </div>

            <div>
              <p class="field-label mb-2 text-[18px] font-semibold text-slate-800">Giấy tờ cần thiết</p>
              <p class="verify-label-row mb-2">
                <span>👥 Giấy tờ pháp lý</span>
                <img :src="infoDotIcon" alt="info" class="h-4 w-4 opacity-70" />
              </p>

              <label class="upload-box">
                <img :src="chooseImageIcon" alt="document" class="mx-auto h-10 w-10 opacity-75" />
                <p class="mt-2 text-xs text-slate-600">Tải lên ảnh chụp rõ nét các mặt của giấy tờ pháp lý</p>
                <span class="upload-pill mt-2">Chọn tệp ảnh</span>
                <input class="hidden" type="file" multiple accept="image/*" @change="onLegalDocumentsChange" />
              </label>
              <div v-if="legalDocumentPreviews.length" class="mt-3">
                <p class="text-xs font-semibold text-slate-700">Ảnh giấy tờ pháp lý ({{ legalDocumentPreviews.length }})</p>
                <div class="preview-grid mt-2">
                  <figure v-for="(preview, idx) in legalDocumentPreviews" :key="preview.url" class="preview-card group">
                    <img :src="preview.url" :alt="preview.name" class="preview-image" />
                    <button
                      type="button"
                      class="absolute right-1 top-1 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-600"
                      title="Xóa ảnh"
                      @click="removeLegalDocument(idx)"
                    >✕</button>
                    <figcaption class="preview-name" :title="preview.name">{{ preview.name }}</figcaption>
                  </figure>
                </div>
              </div>
              <p v-if="verificationUploadError" class="field-error mt-2">{{ verificationUploadError }}</p>
            </div>

            <label class="public-info-box">
              <div class="inline-flex items-start gap-2">
                <input v-model="publicInfoAgreed" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300" />
                <div>
                  <p class="text-[15px] font-semibold text-slate-800">Công khai thông tin</p>
                  <p class="mt-1 text-sm text-slate-600">Giấy tờ của bạn sẽ được che bớt mục: mã QR, vân tay, mã MRZ, đặc điểm nhận dạng,... Theo tiêu chuẩn an toàn và bảo mật thông tin.</p>
                  <p class="text-sm text-slate-600">Các mục còn lại sẽ được công khai tới người đọc.</p>
                </div>
              </div>
            </label>
          </div>
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
          <button type="submit" :disabled="loading" class="w-full rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60">
            {{ loading ? 'Đang đăng tin...' : 'Tiếp tục' }}
          </button>
        </div>
      </form>

      <aside class="hidden lg:block">
        <div class="sticky top-24 space-y-4">

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
                <div class="score-value-wrap">
                  <p class="score-value">{{ mediaPoints }}/4đ</p>
                  <button
                    type="button"
                    class="score-row-toggle"
                    @click="mediaCollapsed = !mediaCollapsed"
                    :aria-label="mediaCollapsed ? 'Mở rộng danh sách ảnh và video' : 'Thu gọn danh sách ảnh và video'"
                  >
                    <span class="score-row-toggle-icon" :class="{ collapsed: mediaCollapsed }" aria-hidden="true"></span>
                  </button>
                </div>
              </div>
              <p class="score-sub">Hoàn thiện {{ mediaPercent }}%</p>

              <div v-if="!mediaCollapsed" class="mt-2 ml-3">
                <div class="flex items-center justify-between text-sm">
                  <div :class="imageCount > 0 ? 'text-emerald-600' : ''">• Hình ảnh</div>
                  <div v-if="imageCount > 0" class="text-emerald-600">✓</div>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                  <div :class="videoPresent ? 'text-emerald-600' : ''">• Video</div>
                  <div v-if="videoPresent" class="text-emerald-600">✓</div>
                </div>
              </div>

              <div class="score-row">
                <div class="score-label">
                  <span class="circle" :class="{ done: infoDone }"></span>
                  <span>Thông tin BĐS</span>
                </div>
                <div class="score-value-wrap">
                  <p class="score-value">{{ formatScorePoint(infoPoints) }}/2đ</p>
                  <button
                    type="button"
                    class="score-row-toggle"
                    @click="infoCollapsed = !infoCollapsed"
                    :aria-label="infoCollapsed ? 'Mở rộng danh sách thông tin bất động sản' : 'Thu gọn danh sách thông tin bất động sản'"
                  >
                    <span class="score-row-toggle-icon" :class="{ collapsed: infoCollapsed }" aria-hidden="true"></span>
                  </button>
                </div>
              </div>
              <p class="score-sub">Hoàn thiện {{ infoPercent }}%</p>
              <div v-if="!infoCollapsed" class="mt-2 ml-3">
                <div
                  v-for="item in infoChecklist"
                  :key="`info-${item.label}`"
                  class="flex items-center justify-between text-sm mt-1"
                >
                  <div :class="item.done ? 'text-emerald-600' : ''">• {{ item.label }}</div>
                  <div v-if="item.done" class="text-emerald-600">✓</div>
                </div>
              </div>

              <div class="score-row">
                <div class="score-label">
                  <span class="circle" :class="{ done: detailDone }"></span>
                  <span>Chi tiết BĐS</span>
                </div>
                <div class="score-value-wrap">
                  <p class="score-value">{{ formatScorePoint(detailPoints) }}/3đ</p>
                  <button
                    type="button"
                    class="score-row-toggle"
                    @click="detailCollapsed = !detailCollapsed"
                    :aria-label="detailCollapsed ? 'Mở rộng danh sách chi tiết bất động sản' : 'Thu gọn danh sách chi tiết bất động sản'"
                  >
                    <span class="score-row-toggle-icon" :class="{ collapsed: detailCollapsed }" aria-hidden="true"></span>
                  </button>
                </div>
              </div>
              <p class="score-sub">Hoàn thiện {{ detailPercent }}%</p>
              <div v-if="!detailCollapsed" class="mt-2 ml-3">
                <div
                  v-for="item in detailChecklist"
                  :key="`detail-${item.label}`"
                  class="flex items-center justify-between text-sm mt-1"
                >
                  <div :class="item.done ? 'text-emerald-600' : ''">• {{ item.label }}</div>
                  <div v-if="item.done" class="text-emerald-600">✓</div>
                </div>
              </div>

              <div class="score-row">
                <div class="score-label">
                  <span class="circle" :class="{ done: contactDone }"></span>
                  <span>Thông tin liên hệ</span>
                </div>
                <div class="score-value-wrap">
                  <p class="score-value" :class="contactDone ? 'text-emerald-600' : ''">{{ formatScorePoint(contactPoints) }}/1đ</p>
                  <button
                    type="button"
                    class="score-row-toggle"
                    @click="contactCollapsed = !contactCollapsed"
                    :aria-label="contactCollapsed ? 'Mở rộng danh sách thông tin liên hệ' : 'Thu gọn danh sách thông tin liên hệ'"
                  >
                    <span class="score-row-toggle-icon" :class="{ collapsed: contactCollapsed }" aria-hidden="true"></span>
                  </button>
                </div>
              </div>
              <p class="score-sub">Hoàn thiện {{ contactPercent }}%</p>
              <div v-if="!contactCollapsed" class="mt-2 ml-3">
                <div
                  v-for="item in contactChecklist"
                  :key="`contact-${item.label}`"
                  class="flex items-center justify-between text-sm mt-1"
                >
                  <div :class="item.done ? 'text-emerald-600' : ''">• {{ item.label }}</div>
                  <div v-if="item.done" class="text-emerald-600">✓</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </aside>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import * as L from "leaflet";
import "leaflet/dist/leaflet.css";
import listingService from "@/services/listingService";
import cloudinaryService from "@/services/cloudinaryService";
import { useRoute, useRouter } from "vue-router";
import AppointmentSlotsForm from "@/components/AppointmentSlotsForm.vue";

import uploadImageIcon from "@/assets/images/listing/postlisting/uploadImage.png";
import locationImageIcon from "@/assets/images/listing/postlisting/locationImage.png";
import informationImageIcon from "@/assets/images/listing/postlisting/information.png";
import homeImageIcon from "@/assets/images/listing/postlisting/homeImage.png";
import documentImageIcon from "@/assets/images/listing/postlisting/document.png";
import chooseImageIcon from "@/assets/images/listing/postlisting/chooseImage.png";
import cameraImageIcon from "@/assets/images/listing/postlisting/camera.png";
import plusImageIcon from "@/assets/images/listing/postlisting/PlusImage.png";
import infoDotIcon from "@/assets/images/listing/postlisting/i.png";
import bedIcon from "@/assets/images/listing/postlisting/giuong.png";
import bathIcon from "@/assets/images/listing/postlisting/bontam.png";

const salePropertyTypeOptions = [
  { value: "APARTMENT", label: "Căn hộ chung cư" },
  { value: "PRIVATE_HOUSE", label: "Nhà riêng" },
  { value: "STREET_HOUSE", label: "Nhà mặt phố" },
  { value: "MINI_APARTMENT", label: "Chung cư mini" },
  { value: "VILLA_TOWNHOUSE", label: "Biệt thự liền kề" },
  { value: "SHOPHOUSE", label: "Shophouse" },
  { value: "KIOSK", label: "Ki-ốt" },
  { value: "OFFICE", label: "Văn phòng" },
  { value: "RESORT", label: "Khu nghỉ dưỡng" },
  { value: "RESTAURANT_HOTEL", label: "Nhà hàng - Khách sạn" },
];

const rentPropertyTypeOptions = [
  { value: "APARTMENT", label: "Căn hộ chung cư" },
  { value: "RENT_ROOM", label: "Phòng trọ" },
  { value: "BOARDING_HOUSE", label: "Nhà trọ" },
  { value: "PRIVATE_HOUSE", label: "Nhà riêng" },
  { value: "STREET_HOUSE", label: "Nhà mặt phố" },
  { value: "MINI_APARTMENT", label: "Chung cư mini" },
  { value: "VILLA_TOWNHOUSE", label: "Biệt thự liền kề" },
  { value: "SHOPHOUSE", label: "Shophouse" },
  { value: "KIOSK", label: "Ki-ốt" },
  { value: "OFFICE", label: "Văn phòng" },
  { value: "RESORT", label: "Khu nghỉ dưỡng" },
  { value: "RESTAURANT_HOTEL", label: "Nhà hàng - Khách sạn" },
];

const legalPaperOptions = [
  { value: "LAND_USE_CERTIFICATE", label: "Giấy CN QSDĐ - Sổ đỏ - Sổ hồng" },
  { value: "SALE_CONTRACT", label: "Hợp đồng mua bán" },
  { value: "CAPITAL_CONTRIBUTION_CONTRACT", label: "Hợp đồng góp vốn" },
  { value: "ALLOTTED_OR_SUBDIVIDED_LAND", label: "Đất giao - Đất phân" },
  { value: "BORROWED_LAND", label: "Đất mượn" },
  { value: "LEASED_LAND", label: "Đất thuê" },
  { value: "ORIGIN_PROOF_DOCUMENT", label: "Giấy tờ chứng minh nguồn gốc" },
  { value: "NO_LAND_CERTIFICATE", label: "Chưa làm giấy CN QSDĐ" },
  { value: "PROCESSING_LAND_CERTIFICATE", label: "Đang làm giấy CN QSDĐ" },
  { value: "APPOINTMENT_FOR_CERTIFICATE", label: "Đã có giấy hẹn lấy sổ" },
  { value: "BUSINESS_TRANSFER", label: "Sang nhượng doanh nghiệp" },
  { value: "SHARE_TRANSFER", label: "Mua bán cổ phần" },
  { value: "INVESTMENT_COOPERATION", label: "Hợp tác đầu tư" },
  { value: "HANDWRITTEN", label: "Viết tay" },
];

const quickNumberOptions = [1, 2, 3, 4, 5];
const amenityOptions = ["Sân chơi", "Bể bơi", "Sân vườn", "Thang máy", "Wifi", "Khu để xe"];
const directionOptions = [
  { value: "N", label: "Bắc" },
  { value: "NE", label: "Đông Bắc" },
  { value: "E", label: "Đông" },
  { value: "SE", label: "Đông Nam" },
  { value: "S", label: "Nam" },
  { value: "SW", label: "Tây Nam" },
  { value: "W", label: "Tây" },
  { value: "NW", label: "Tây Bắc" },
];

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
    legalPaperTypes: [],
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
    amenities: [],
    publicInfoAgreed: false,
    requestVerification: false,
    identityCardFront: null,
    identityCardBack: null,
    legalDocuments: [],
    // Rental-specific fields
    rentMinTerm: '',
    rentPaymentInterval: '',
    rentDeposit: '',
  };
}

const form = reactive(createInitialState());
const route = useRoute();
const router = useRouter();
const isEditMode = computed(() => !!route.params.id);
const editListingId = computed(() => route.params.id);
const isHydratingEdit = ref(false);
const isSyncingAdminFromMap = ref(false);
const editLoading = ref(false);
const loading = ref(false);
const submitError = ref("");
const validationErrors = ref({});
const touchedFields = reactive({});
const locationSearchText = ref("");
const mapElement = ref(null);
let map = null;
let locationMarker = null;
const provinces = ref([]);
const wards = ref([]);
const wardsLoading = ref(false);

const locationSearching = ref(false);
const locationLoadError = ref("");
const imagePreviews = ref([]);
const frontCardPreviewUrl = ref("");
const backCardPreviewUrl = ref("");
const legalDocumentPreviews = ref([]);
const showLegalDropdown = ref(false);
const showMoreDetail = ref(true);
const selectedAmenities = ref([]);
const showVerificationSection = ref(true);
const publicInfoAgreed = ref(false);
const appointmentForm = ref(null);
const pendingAppointmentRows = ref(null);
const existingAppointmentSlotIds = ref([]);
const imageUploadError = ref("");
const videoUploadError = ref("");
const verificationUploadError = ref("");
const videoPreviewName = ref("");
const mediaCollapsed = ref(false);
const infoCollapsed = ref(false);
const detailCollapsed = ref(false);
const contactCollapsed = ref(false);

const toasts = ref([]);
let toastIdCounter = 1;

function pushToast(message, type = "info", duration = 2500) {
  const id = toastIdCounter++;
  toasts.value = [...toasts.value, { id, message, type }];
  setTimeout(() => {
    toasts.value = toasts.value.filter((item) => item.id !== id);
  }, duration);
}

const imageCount = computed(() => Array.isArray(form.images) ? form.images.length : 0);
const videoPresent = computed(() => Boolean(form.video));

// Image points: 0 -> 0, 1 -> 1, 2-3 -> 1, >=4 -> 2
const imagePoints = computed(() => {
  const c = imageCount.value || 0;
  if (c === 0) return 0;
  if (c === 1) return 1;
  if (c >= 2 && c < 4) return 1;
  return 2;
});

// Video gives 2 points if present
const videoPoints = computed(() => (videoPresent.value ? 2 : 0));

const mediaPoints = computed(() => Math.min(imagePoints.value + videoPoints.value, 4));
const mediaPercent = computed(() => Math.round((mediaPoints.value / 4) * 100));
const mediaDone = computed(() => mediaPoints.value === 4);

const infoChecklist = computed(() => [
  { label: 'Nhu cầu', done: Boolean(form.demandType) },
  { label: 'Tiêu đề', done: Boolean(form.title?.trim()) },
  { label: 'Mô tả', done: Boolean(form.description?.trim()) },
  { label: 'Loại nhà đất', done: Boolean(form.propertyType?.trim()) },
  { label: 'Giấy tờ pháp lý', done: Array.isArray(form.legalPaperTypes) && form.legalPaperTypes.length > 0 },
  { label: 'Diện tích', done: Number(form.area) > 0 },
  { label: form.demandType === 'RENT' ? 'Giá thuê' : 'Giá bán', done: form.isNegotiable || Number(form.price) > 0 },
  { label: 'Dự án', done: Boolean(form.projectName?.trim()) },
  { label: 'Tỉnh/thành phố', done: Boolean(form.provinceCode?.trim()) },
  { label: 'Quận/huyện', done: Boolean(form.districtCode?.trim()) },
  { label: 'Xã/phường', done: Boolean(form.wardCode?.trim()) },
  { label: 'Đường/phố', done: Boolean(form.streetCode?.trim()) },
  { label: 'Địa chỉ cụ thể', done: Boolean(form.addressDetail?.trim()) },
]);

const infoFilledCount = computed(() => infoChecklist.value.filter((item) => item.done).length);
const infoTotalCount = computed(() => infoChecklist.value.length || 1);
const infoPercent = computed(() => Math.round((infoFilledCount.value / infoTotalCount.value) * 100));
const infoDone = computed(() => infoFilledCount.value === infoTotalCount.value);
const infoPoints = computed(() => Number(((infoFilledCount.value / infoTotalCount.value) * 2).toFixed(1)));

const detailChecklist = computed(() => [
  { label: 'Số tầng', done: String(form.floors ?? '').trim() !== '' },
  { label: 'Tầng thứ', done: String(form.floorNumber ?? '').trim() !== '' },
  { label: 'Mặt tiền', done: String(form.facadeWidth ?? '').trim() !== '' },
  { label: 'Chiều sâu', done: String(form.depth ?? '').trim() !== '' },
  { label: 'Đường rộng', done: String(form.roadWidth ?? '').trim() !== '' },
  { label: 'Số phòng ngủ', done: String(form.bedrooms ?? '').trim() !== '' },
  { label: 'Số phòng tắm', done: String(form.bathrooms ?? '').trim() !== '' },
  { label: 'Hướng ban công', done: Boolean(form.balconyDirectionCode) },
  { label: 'Số ban công', done: String(form.balconies ?? '').trim() !== '' },
  { label: 'Hướng nhà/đất', done: Boolean(form.directionCode) },
  { label: 'Nội thất', done: Boolean(form.furnitureStatus) },
  { label: 'Tiện ích', done: Array.isArray(selectedAmenities.value) && selectedAmenities.value.length > 0 },
]);

const detailFilledCount = computed(() => detailChecklist.value.filter((item) => item.done).length);
const detailTotalCount = computed(() => detailChecklist.value.length || 1);
const detailPercent = computed(() => Math.round((detailFilledCount.value / detailTotalCount.value) * 100));
const detailPoints = computed(() => Number(((detailFilledCount.value / detailTotalCount.value) * 3).toFixed(1)));
const detailDone = computed(() => detailFilledCount.value === detailTotalCount.value);

const isContactNameValid = computed(() => {
  if (!form.contactName || !String(form.contactName).trim()) return false;
  return /^[\p{L}\s'.-]+$/u.test(String(form.contactName).trim());
});

const isContactPhoneValid = computed(() => {
  if (!form.contactPhone) return false;
  return /^0[0-9]{9}$/.test(String(form.contactPhone));
});

const isContactEmailValid = computed(() => {
  if (!form.contactEmail || !String(form.contactEmail).trim()) return false;
  const email = String(form.contactEmail).trim().toLowerCase();
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
});

const contactChecklist = computed(() => [
  { label: 'Đối tượng', done: Boolean(form.posterType) },
  { label: 'Họ và tên', done: isContactNameValid.value },
  { label: 'Số điện thoại', done: isContactPhoneValid.value },
  { label: 'Email', done: isContactEmailValid.value },
]);

const contactFilledCount = computed(() => contactChecklist.value.filter((item) => item.done).length);
const contactTotalCount = computed(() => contactChecklist.value.length || 1);
const contactDone = computed(() => contactFilledCount.value === contactTotalCount.value);
const contactPoints = computed(() => Number(((contactFilledCount.value / contactTotalCount.value) * 1).toFixed(1)));
const contactPercent = computed(() => Math.round((contactFilledCount.value / contactTotalCount.value) * 100));

function formatScorePoint(value) {
  return Number.isInteger(value) ? String(value) : value.toFixed(1);
}

const totalScore = computed(() => {
  return mediaPoints.value + infoPoints.value + detailPoints.value + contactPoints.value;
});

const selectedLegalPaperLabels = computed(() => {
  return legalPaperOptions
    .filter((option) => form.legalPaperTypes.includes(option.value))
    .map((option) => option.label)
    .join(", ");
});

// Refs for click-outside handling of the legal dropdown
const legalTriggerRef = ref(null);
const legalDropdownRef = ref(null);

function onDocumentClick(e) {
  if (!showLegalDropdown.value) return;
  const triggerEl = legalTriggerRef.value;
  const dropdownEl = legalDropdownRef.value;
  const target = e.target;
  if (triggerEl && triggerEl.contains && triggerEl.contains(target)) return;
  if (dropdownEl && dropdownEl.contains && dropdownEl.contains(target)) return;
  showLegalDropdown.value = false;
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

// If the appointment component wasn't mounted when we loaded edit data,
// assign pending rows once the ref becomes available.
watch(appointmentForm, (v) => {
  if (v && pendingAppointmentRows.value) {
    appointmentForm.value.appointmentRows = pendingAppointmentRows.value;
    pendingAppointmentRows.value = null;
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});

const shouldRequestVerification = computed(() => {
  return Boolean(form.identityCardFront || form.identityCardBack || (form.legalDocuments && form.legalDocuments.length));
});

const titleCount = computed(() => normalizeSingleLineText(form.title).length);
const descriptionCount = computed(() => normalizeMultilineText(form.description).length);
const priceLabel = computed(() => (form.demandType === "RENT" ? "Giá thuê" : "Giá bán"));

const currentPropertyTypeOptions = computed(() =>
  form.demandType === "RENT" ? rentPropertyTypeOptions : salePropertyTypeOptions,
);

const selectedProvinceName = computed(() => {
  const item = provinces.value.find((province) => String(province.code) === form.provinceCode);
  return item?.name || "";
});



watch(
  () => form.demandType,
  () => {
    const hasSelectedType = currentPropertyTypeOptions.value.some(
      (option) => option.value === form.propertyType,
    );
    if (!hasSelectedType) {
      form.propertyType = currentPropertyTypeOptions.value[0]?.value ?? "APARTMENT";
    }
  },
);

watch(
  () => form.provinceCode,
  async (newValue) => {
    if (isHydratingEdit.value || isSyncingAdminFromMap.value) return;

    form.wardCode = "";
    wards.value = [];

    if (!newValue) return;
    await fetchWardsByProvince(newValue);
    await geocodeAddressToMap(composeAddressQuery({ includeDetail: false }), 11);
  },
);

// Auto-save form to draft
watch(form, () => saveFormToDraft(), { deep: true });
watch([selectedAmenities, publicInfoAgreed], () => saveFormToDraft(), { deep: true });

onMounted(async () => {
  initializeMap();
  await fetchProvinces();

  if (isEditMode.value) {
    clearDraft(); // Xóa draft khi vào form sửa
    await loadListingForEdit();
  } else {
    loadFormFromDraft();
  }
});

async function loadListingForEdit() {
  editLoading.value = true;
  isHydratingEdit.value = true;
  try {
    const response = await listingService.getById(editListingId.value);
    const data = response.data.data;
    const p = data.property || {};

    form.demandType = data.demand_type || 'SALE';
    form.title = data.title || '';
    form.description = data.description || '';
    form.propertyType = p.type || 'APARTMENT';
    form.provinceCode = p.province_code ? String(p.province_code) : '';
    form.districtCode = p.district_code ? String(p.district_code) : '';
    form.wardCode = p.ward_code ? String(p.ward_code) : '';
    form.streetCode = p.street_code || '';
    form.projectName = p.project_name || '';
    form.addressDetail = p.address_detail || '';
    form.area = p.area ?? '';
    form.price = p.price ?? '';
    form.isNegotiable = p.is_negotiable || false;
    form.bedrooms = p.bedrooms ?? '';
    form.bathrooms = p.bathrooms ?? '';
    form.floors = p.floors ?? '';
    form.floorNumber = p.floor_number ?? '';
    form.balconies = p.balconies ?? '';
    form.facadeWidth = p.facade_width ?? '';
    form.depth = p.depth ?? '';
    form.roadWidth = p.road_width ?? '';
    form.directionCode = p.direction_code || '';
    form.balconyDirectionCode = p.balcony_direction_code || '';
    form.furnitureStatus = p.furniture_status || '';
    form.legalPaperTypes = p.legal_paper_types || [];
    form.contactName = p.contact_name || '';
    form.contactPhone = p.contact_phone || '';
    form.contactEmail = p.contact_email || '';
    form.posterType = p.poster_type || 'OWNER';
    form.lat = p.lat ?? '';
    form.lng = p.lng ?? '';
    form.amenities = p.amenities || [];
    form.publicInfoAgreed = Boolean(p.public_info_agreed);
    form.rentMinTerm = data.rent_min_term || '';
    form.rentPaymentInterval = data.rent_payment_interval || '';
    form.rentDeposit = data.rent_deposit || '';
    selectedAmenities.value = [...(p.amenities || [])];
    publicInfoAgreed.value = Boolean(p.public_info_agreed);

    const verificationDocuments = Array.isArray(data.verification_documents) ? data.verification_documents : [];
    const idFrontDoc = verificationDocuments.find((doc) => doc.type === 'ID_FRONT');
    const idBackDoc = verificationDocuments.find((doc) => doc.type === 'ID_BACK');
    const legalDocs = verificationDocuments.filter((doc) => doc.type === 'LEGAL_DOCUMENT');

    frontCardPreviewUrl.value = idFrontDoc?.url || '';
    backCardPreviewUrl.value = idBackDoc?.url || '';
    legalDocumentPreviews.value = legalDocs.map((doc, index) => ({
      name: `Giấy tờ pháp lý ${index + 1}`,
      url: doc.url,
    }));
    form.identityCardFront = idFrontDoc?.url || null;
    form.identityCardBack = idBackDoc?.url || null;
    form.legalDocuments = legalDocs.map((doc) => doc.url);

    if (data.appointment_slots && Array.isArray(data.appointment_slots)) {
      existingAppointmentSlotIds.value = data.appointment_slots.map((slot) => slot.id).filter(Boolean);
      const groupedByTime = {};
      data.appointment_slots.forEach((slot) => {
        const key = `${slot.start_time}-${slot.end_time}`;
        if (!groupedByTime[key]) {
          groupedByTime[key] = { start_time: slot.start_time, end_time: slot.end_time, selected_days: [] };
        }
        groupedByTime[key].selected_days.push(slot.day_of_week);
      });
      const rows = Object.values(groupedByTime).map((row) => ({
        start_time: row.start_time,
        end_time: row.end_time,
        selected_days: row.selected_days.sort((a, b) => a - b),
      }));
      if (appointmentForm.value) {
        appointmentForm.value.appointmentRows = rows;
      } else {
        pendingAppointmentRows.value = rows;
      }
    } else {
      existingAppointmentSlotIds.value = [];
    }



    // Load existing images as URLs (not File objects)
    if (data.images && data.images.length > 0) {
      const sorted = [...data.images].sort((a, b) => a.sort_order - b.sort_order);
      form.images = sorted.map(img => img.url);
      imagePreviews.value = sorted.map((img, index) => ({
        name: `Ảnh ${index + 1}`,
        url: img.url,
      }));
    }

    if (Array.isArray(data.videos) && data.videos.length > 0) {
      const firstVideo = data.videos[0];
      form.video = firstVideo?.url || null;
      videoPreviewName.value = firstVideo?.url ? 'Video hiện tại' : '';
    }

    // Load wards list for the selected province
    if (form.provinceCode) {
      await fetchWardsByProvince(form.provinceCode);
    }

    // Set map marker if lat/lng exists
    if (form.lat && form.lng) {
      setTimeout(() => {
        setMarkerPosition(form.lat, form.lng, 15);
      }, 500);
    }
  } catch (err) {
    console.error('Failed to load listing for edit:', err);
    submitError.value = 'Không thể tải dữ liệu tin đăng để chỉnh sửa.';
  } finally {
    isHydratingEdit.value = false;
    editLoading.value = false;
  }
}

function initializeMap() {
  if (!mapElement.value || map) return;

  map = L.map(mapElement.value, {
    zoomControl: true,
  }).setView([10.7769, 106.7009], 11);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "",
  }).addTo(map);

  map.on("click", async (event) => {
    const { lat, lng } = event.latlng;
    setMarkerPosition(lat, lng, 16);
    await reverseGeocodeFromLatLng(lat, lng);
  });
}

function setMarkerPosition(lat, lng, zoom = 16) {
  form.lat = Number(lat).toFixed(7);
  form.lng = Number(lng).toFixed(7);

  if (!map) return;

  if (!locationMarker) {
    locationMarker = L.circleMarker([lat, lng], {
      radius: 8,
      color: "#2563eb",
      fillColor: "#3b82f6",
      fillOpacity: 0.9,
      weight: 2,
    }).addTo(map);
  } else {
    locationMarker.setLatLng([lat, lng]);
  }

  map.setView([lat, lng], zoom);
}

function composeAddressQuery({ includeDetail = true } = {}) {
  const chunks = [];

  if (includeDetail && form.addressDetail?.trim()) {
    chunks.push(form.addressDetail.trim());
  }

  if (form.streetCode?.trim()) {
    chunks.push(form.streetCode.trim());
  }

  if (selectedProvinceName.value) chunks.push(selectedProvinceName.value);

  chunks.push("Việt Nam");
  return chunks.filter(Boolean).join(", ");
}

async function geocodeAddressToMap(address, zoom = 15) {
  if (!address || !map) return;

  try {
    locationSearching.value = true;
    const params = new URLSearchParams({ q: address });

    const response = await fetch(`${import.meta.env.VITE_API_URL}/v1/geocoding/search?${params.toString()}`);
    if (!response.ok) return;

    const data = await response.json();
    if (!Array.isArray(data) || data.length === 0) return;

    const [result] = data;
    const lat = Number(result.lat);
    const lng = Number(result.lon);

    setMarkerPosition(lat, lng, zoom);
  } catch {
    // Silent fail to avoid interrupting the form flow.
  } finally {
    locationSearching.value = false;
  }
}

async function reverseGeocodeFromLatLng(lat, lng) {
  try {
    const params = new URLSearchParams({
      lat: String(lat),
      lng: String(lng),
    });
    const response = await fetch(`${import.meta.env.VITE_API_URL}/v1/geocoding/reverse?${params.toString()}`);
    if (!response.ok) return;

    const data = await response.json();
    if (!data || data.error) return;

    locationSearchText.value = data.display_name || "";

    const road = data.address?.road || data.address?.pedestrian || data.address?.residential || "";
    if (road) {
      form.streetCode = road;
    }

    const houseNumber = data.address?.house_number || "";
    form.addressDetail = [houseNumber, road].filter(Boolean).join(" ").trim();

    await syncAdministrativeCodesFromAddress(data.address || {}, data.display_name || "");
  } catch {
    // Ignore reverse geocode failures.
  }
}

function normalizeAdminName(value) {
  return String(value || "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/thanh pho |tinh |tp\.? ?|city |province /g, "")
    .replace(/ ?(thanh pho|tinh|tp|city|province)$/g, "")
    .replace(/[^a-z0-9\s]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

/**
 * Parse display_name từ Nominatim để lấy các segment địa chỉ.
 * VD: "Sở Tài nguyên, Lê Tự Trọng, Khu phố 3, Phường Sài Gòn, Thành phố Thủ Đức, Thành phố Hồ Chí Minh, 71006, Việt Nam"
 */
function parseDisplayNameSegments(displayName) {
  const text = String(displayName || "");
  return text
    .split(",")
    .map((s) => s.trim())
    .filter((s) => s && !/^\d{5,}$/.test(s) && s !== "Việt Nam" && s !== "Vietnam");
}

function findProvinceByAddress(address, displayName = "") {
  const primaryCandidates = [address.state, address.province, address.county, address.region].filter(Boolean);

  // Nominatim dùng địa chính mới, city có thể không phải province
  // → parse từ display_name: segment cuối trước "Việt Nam" thường là province
  const segments = parseDisplayNameSegments(displayName);
  const tailCandidates = segments.slice(-4).filter(Boolean);
  const secondaryCandidates = [address.city, address.town].filter(Boolean);

  const allCandidates = [...primaryCandidates, ...tailCandidates, ...secondaryCandidates];
  if (!allCandidates.length) return null;

  const exactMatch = provinces.value.find((province) => {
    const provinceName = normalizeAdminName(province.name);
    return allCandidates.some((c) => normalizeAdminName(c) === provinceName);
  });
  if (exactMatch) return exactMatch;

  return provinces.value.find((province) => {
    const provinceName = normalizeAdminName(province.name);
    return allCandidates.some((c) => {
      const nc = normalizeAdminName(c);
      return nc && (provinceName.includes(nc) || nc.includes(provinceName));
    });
  }) || null;
}

async function syncAdministrativeCodesFromAddress(address, displayName = "") {
  isSyncingAdminFromMap.value = true;

  try {
    const province = findProvinceByAddress(address, displayName);
    if (!province) return;

    if (String(province.code) !== form.provinceCode) {
      form.provinceCode = String(province.code);
      await fetchWardsByProvince(form.provinceCode);
    } else if (!wards.value.length) {
      await fetchWardsByProvince(form.provinceCode);
    }

    // Thử match phường/xã từ Nominatim
    const ward = findWardByAddress(address, displayName);
    form.wardCode = ward ? String(ward.code) : "";
  } finally {
    isSyncingAdminFromMap.value = false;
  }
}

function normalizeWardName(value) {
  return String(value || "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/phuong |xa |thi tran |ward |commune /g, "")
    .replace(/ ?(phuong|xa|thi tran|ward|commune)$/g, "")
    .replace(/[^a-z0-9\s]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function findWardByAddress(address, displayName = "") {
  const wardFromDisplay = extractWardFromDisplayName(displayName);
  const candidates = [
    address.suburb,
    address.quarter,
    address.ward,
    address.village,
    address.hamlet,
    wardFromDisplay,
  ].filter(Boolean);
  if (!candidates.length) return null;

  const normalizedCandidates = candidates.map((c) => normalizeWardName(c)).filter(Boolean);

  const exactMatch = wards.value.find((ward) => {
    const wn = normalizeWardName(ward.name);
    return normalizedCandidates.some((c) => wn === c);
  });
  if (exactMatch) return exactMatch;

  return wards.value.find((ward) => {
    const wn = normalizeWardName(ward.name);
    return normalizedCandidates.some((c) => wn.includes(c) || c.includes(wn));
  }) || null;
}

function extractWardFromDisplayName(displayName) {
  const text = String(displayName || "");
  if (!text) return "";
  const match = text.match(/\b(Phường\s+[^,]+|Xã\s+[^,]+|Thị trấn\s+[^,]+)/i);
  return match?.[1]?.trim() || "";
}

async function searchAddressOnMap() {
  const query = locationSearchText.value?.trim() || composeAddressQuery({ includeDetail: true });
  if (!query) return;
  await geocodeAddressToMap(query, 16);
}

async function fetchProvinces() {
  locationLoadError.value = "";
  try {
    const response = await fetch("https://provinces.open-api.vn/api/v2/p/");
    if (!response.ok) {
      throw new Error("Không thể tải danh sách tỉnh/thành phố");
    }
    provinces.value = await response.json();
  } catch (error) {
    locationLoadError.value = error.message || "Không thể tải dữ liệu vị trí";
  }
}

/**
 * Load phường/xã theo tỉnh/thành phố từ API v2 (2025).
 * API mới không có quận/huyện — phường/xã trực thuộc tỉnh.
 */
async function fetchWardsByProvince(provinceCode) {
  locationLoadError.value = "";
  wardsLoading.value = true;
  try {
    const response = await fetch(`https://provinces.open-api.vn/api/v2/w/?province=${provinceCode}`);
    if (!response.ok) {
      throw new Error("Không thể tải danh sách phường/xã");
    }
    const data = await response.json();
    // API v2 trả về mảng phẳng phường/xã, sắp xếp theo tên
    const sorted = Array.isArray(data) ? data : [];
    sorted.sort((a, b) => a.name.localeCompare(b.name, 'vi'));
    wards.value = sorted;
  } catch (error) {
    locationLoadError.value = error.message || "Không thể tải dữ liệu phường/xã";
  } finally {
    wardsLoading.value = false;
  }
}



function onImagesChange(event) {
  touchField('images');
  const newFiles = event.target.files ? Array.from(event.target.files) : [];
  imageUploadError.value = "";

  // Nếu người dùng Cancel, files rỗng → giữ nguyên ảnh cũ
  if (newFiles.length === 0) return;

  const validExtensions = ["image/jpeg", "image/png", "image/jpg"];
  const MAX_IMAGE_SIZE = 30 * 1024 * 1024;

  const validNewFiles = [];
  for (const file of newFiles) {
    if (!validExtensions.includes(file.type)) {
      imageUploadError.value = "Định dạng ảnh không hợp lệ (chỉ JPG, PNG)";
      pushToast("File không hợp lệ đã bị loại bỏ", "warning");
      continue;
    }

    if (file.size > MAX_IMAGE_SIZE) {
      imageUploadError.value = "Dung lượng ảnh vượt quá 30MB";
      pushToast("File không hợp lệ đã bị loại bỏ", "warning");
      continue;
    }

    validNewFiles.push(file);
  }

  if (validNewFiles.length === 0) {
    event.target.value = '';
    return;
  }

  // Lấy danh sách key của ảnh đã có để tránh trùng lặp (dựa theo tên + size)
  const existingKeys = new Set(
    form.images.map((f) => (typeof f === 'string' ? f : `${f.name}_${f.size}`))
  );

  // Chỉ thêm những file chưa có trong danh sách
  const uniqueNewFiles = validNewFiles.filter(
    (f) => !existingKeys.has(`${f.name}_${f.size}`)
  );

  if (uniqueNewFiles.length === 0) {
    event.target.value = '';
    return;
  }

  // Giới hạn tối đa 10 ảnh
  const MAX = 10;
  const remaining = MAX - form.images.length;
  if (remaining <= 0) {
    imageUploadError.value = "Bạn chỉ có thể tải tối đa 10 hình ảnh";
    pushToast("Không thể tải thêm file", "warning");
    event.target.value = '';
    return;
  }

  const filesToAdd = uniqueNewFiles.slice(0, remaining);
  if (uniqueNewFiles.length > filesToAdd.length) {
    imageUploadError.value = "Bạn chỉ có thể tải tối đa 10 hình ảnh";
    pushToast("Không thể tải thêm file", "warning");
  }

  if (filesToAdd.length > 0) {
    pushToast("Đang tải lên hình ảnh...", "info", 1400);
  }

  // Thêm vào cuối danh sách hiện có (không xóa ảnh cũ)
  form.images = [...form.images, ...filesToAdd];
  imagePreviews.value = [
    ...imagePreviews.value,
    ...filesToAdd.map((file) => ({
      name: file.name,
      url: URL.createObjectURL(file),
      file,
    })),
  ];

  // Reset input để có thể chọn lại cùng file nếu cần
  event.target.value = '';
}

function onVideoChange(event) {
  videoUploadError.value = "";
  const file = event.target.files?.[0] || null;
  if (!file) return;

  if (form.video) {
    videoUploadError.value = "Chỉ cho phép 1 video";
    pushToast("Không thể tải thêm file", "warning");
    event.target.value = '';
    return;
  }

  if (file.type !== 'video/mp4') {
    videoUploadError.value = "Định dạng video không hợp lệ (MP4)";
    pushToast("File không hợp lệ đã bị loại bỏ", "warning");
    event.target.value = '';
    return;
  }

  const MAX_VIDEO_SIZE = 100 * 1024 * 1024;
  if (file.size > MAX_VIDEO_SIZE) {
    videoUploadError.value = "Dung lượng video vượt quá 100MB";
    pushToast("File không hợp lệ đã bị loại bỏ", "warning");
    event.target.value = '';
    return;
  }

  form.video = file;
  videoPreviewName.value = file.name;
  pushToast("Đang tải lên video...", "info", 1400);
  event.target.value = '';
}

function removeVideo() {
  form.video = null;
  videoPreviewName.value = "";
  videoUploadError.value = "";
  pushToast("Đã xóa video", "success");
}

function removeImage(index) {
  touchField('images');
  // Giải phóng object URL của ảnh bị xóa
  const removed = imagePreviews.value[index];
  if (removed?.url) URL.revokeObjectURL(removed.url);

  imagePreviews.value = imagePreviews.value.filter((_, i) => i !== index);
  form.images = form.images.filter((_, i) => i !== index);
  pushToast("Đã xóa hình ảnh", "success");
}

function toggleLegalDropdown() {
  showLegalDropdown.value = !showLegalDropdown.value;
}

function toggleLegalPaper(value) {
  if (form.legalPaperTypes.includes(value)) {
    form.legalPaperTypes = form.legalPaperTypes.filter((item) => item !== value);
    return;
  }
  form.legalPaperTypes = [...form.legalPaperTypes, value];
}

async function uploadVerificationImage(file) {
  if (!file) return null;
  if (typeof file === 'string') return file;
  const res = await cloudinaryService.uploadImage(file, 'listing');
  return res.secure_url;
}

async function onFrontCardChange(event) {
  if (frontCardPreviewUrl.value && String(frontCardPreviewUrl.value).startsWith('blob:')) {
    URL.revokeObjectURL(frontCardPreviewUrl.value);
  }
  const file = event.target.files?.[0] || null;
  if (!file) return;

  const localPreviewUrl = URL.createObjectURL(file);
  frontCardPreviewUrl.value = localPreviewUrl;
  try {
    pushToast('Đang tải lên hình ảnh...', 'info', 1200);
    const uploadedUrl = await uploadVerificationImage(file);
    form.identityCardFront = uploadedUrl;
    if (frontCardPreviewUrl.value === localPreviewUrl) {
      URL.revokeObjectURL(localPreviewUrl);
      frontCardPreviewUrl.value = uploadedUrl;
    }
  } catch (error) {
    form.identityCardFront = null;
    frontCardPreviewUrl.value = '';
    verificationUploadError.value = 'Không thể tải ảnh CCCD mặt trước';
    pushToast('Không thể tải ảnh CCCD mặt trước', 'error');
  }
}

async function onBackCardChange(event) {
  if (backCardPreviewUrl.value && String(backCardPreviewUrl.value).startsWith('blob:')) {
    URL.revokeObjectURL(backCardPreviewUrl.value);
  }
  const file = event.target.files?.[0] || null;
  if (!file) return;

  const localPreviewUrl = URL.createObjectURL(file);
  backCardPreviewUrl.value = localPreviewUrl;
  try {
    pushToast('Đang tải lên hình ảnh...', 'info', 1200);
    const uploadedUrl = await uploadVerificationImage(file);
    form.identityCardBack = uploadedUrl;
    if (backCardPreviewUrl.value === localPreviewUrl) {
      URL.revokeObjectURL(localPreviewUrl);
      backCardPreviewUrl.value = uploadedUrl;
    }
  } catch (error) {
    form.identityCardBack = null;
    backCardPreviewUrl.value = '';
    verificationUploadError.value = 'Không thể tải ảnh CCCD mặt sau';
    pushToast('Không thể tải ảnh CCCD mặt sau', 'error');
  }
}

async function onLegalDocumentsChange(event) {
  verificationUploadError.value = "";
  const files = event.target.files ? Array.from(event.target.files) : [];
  if (!files.length) return;

  const validExtensions = ["image/jpeg", "image/png", "image/jpg"];
  const MAX_DOC_SIZE = 10 * 1024 * 1024;
  const MAX_DOC_COUNT = 5;

  const validFiles = [];
  for (const file of files) {
    if (!validExtensions.includes(file.type)) {
      verificationUploadError.value = "Định dạng file không hợp lệ";
      pushToast("File không hợp lệ đã bị loại bỏ", "warning");
      continue;
    }

    if (file.size > MAX_DOC_SIZE) {
      verificationUploadError.value = "Dung lượng file vượt quá giới hạn";
      pushToast("File không hợp lệ đã bị loại bỏ", "warning");
      continue;
    }

    validFiles.push(file);
  }

  if (!validFiles.length) {
    event.target.value = '';
    return;
  }

  const remaining = MAX_DOC_COUNT - legalDocumentPreviews.value.length;
  if (remaining <= 0) {
    verificationUploadError.value = "Tối đa 5 ảnh giấy tờ pháp lý";
    pushToast("Không thể tải thêm file", "warning");
    event.target.value = '';
    return;
  }

  const filesToAdd = validFiles.slice(0, remaining);
  if (validFiles.length > filesToAdd.length) {
    verificationUploadError.value = "Tối đa 5 ảnh giấy tờ pháp lý";
    pushToast("Không thể tải thêm file", "warning");
  }

  const newPreviews = filesToAdd.map((file) => ({
    name: file.name,
    url: URL.createObjectURL(file),
    file,
  }));
  legalDocumentPreviews.value = [...legalDocumentPreviews.value, ...newPreviews];
  form.legalDocuments = [...form.legalDocuments, ...filesToAdd];

  event.target.value = '';
}

function removeLegalDocument(index) {
  const removed = legalDocumentPreviews.value[index];
  if (removed?.url && String(removed.url).startsWith('blob:')) {
    URL.revokeObjectURL(removed.url);
  }
  legalDocumentPreviews.value = legalDocumentPreviews.value.filter((_, i) => i !== index);
  form.legalDocuments = form.legalDocuments.filter((_, i) => i !== index);
  pushToast("Đã xóa hình ảnh", "success");
}

function setQuickNumber(field, value) {
  form[field] = value;
}

function normalizeSingleLineText(value) {
  return String(value ?? "")
    .replace(/\s+/g, " ")
    .trim();
}

function normalizeMultilineText(value) {
  return String(value ?? "")
    .replace(/\r\n/g, "\n")
    .split("\n")
    .map((line) => line.replace(/[\t ]+/g, " ").trim())
    .join("\n")
    .trim();
}

function handleTextBlur(field, multiline = false) {
  const normalizedValue = multiline ? normalizeMultilineText(form[field]) : normalizeSingleLineText(form[field]);
  form[field] = normalizedValue;
  touchField(field);
}

function normalizeFormTextFields() {
  form.title = normalizeSingleLineText(form.title);
  form.description = normalizeMultilineText(form.description);
  form.streetCode = normalizeSingleLineText(form.streetCode);
  form.addressDetail = normalizeSingleLineText(form.addressDetail);
  form.contactName = normalizeSingleLineText(form.contactName);
  form.contactEmail = normalizeSingleLineText(form.contactEmail).toLowerCase();
  locationSearchText.value = normalizeSingleLineText(locationSearchText.value);
}

// ── Validation helpers ──
function touchField(field) {
  touchedFields[field] = true;
}

function fieldError(field) {
  return Boolean(fieldErrorMessage(field));
}

function fieldErrorMessage(field) {
  if (!touchedFields[field]) return "";

  if (field === 'images') {
    if (!Array.isArray(form.images) || form.images.length === 0) return 'Vui lòng tải lên ít nhất 1 hình ảnh';
    return '';
  }

  const value = form[field];

  if (field === 'title') {
    if (!value) return 'Tiêu đề không được để trống';
    if (!String(value).trim()) return 'Tiêu đề không hợp lệ';
    return '';
  }

  if (field === 'description') {
    if (!value || !String(value).trim()) return 'Mô tả không được để trống';
    if (String(value).trim().length < 20) return 'Mô tả phải có ít nhất 20 ký tự';
    return '';
  }

  if (field === 'propertyType') {
    if (!value || !String(value).trim()) return 'Vui lòng chọn loại nhà đất';
    return '';
  }

  if (field === 'provinceCode') {
    if (!value || !String(value).trim()) return 'Vui lòng chọn Tỉnh/Thành phố';
    return '';
  }

  if (field === 'wardCode') {
    if (!value || !String(value).trim()) return 'Vui lòng chọn Phường/Xã';
    return '';
  }

  if (field === 'streetCode') {
    if (!value || !String(value).trim()) return 'Vui lòng nhập Đường/Phố';
    return '';
  }

  if (field === 'addressDetail') {
    if (!value || !String(value).trim()) return 'Vui lòng nhập địa chỉ cụ thể';
    return '';
  }

  if (field === 'price') {
    if (form.isNegotiable) return '';
    if (!value) return 'Giá phải lớn hơn 0';
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) return 'Giá không hợp lệ';
    if (parsed <= 0) return 'Giá phải lớn hơn 0';
    return '';
  }

  if (field === 'rentMinTerm') {
    if (form.demandType !== 'RENT') return '';
    // optional in UI, keep validation lenient (only error if explicitly touched)
    if (touchedFields['rentMinTerm'] && !value) return 'Vui lòng chọn thời gian thuê tối thiểu';
    return '';
  }

  if (field === 'rentPaymentInterval') {
    if (form.demandType !== 'RENT') return '';
    if (touchedFields['rentPaymentInterval'] && !value) return 'Vui lòng chọn kỳ thanh toán';
    return '';
  }

  if (field === 'area') {
    if (!value) return 'Diện tích không hợp lệ hoặc vượt giới hạn';
    const parsed = Number(value);
    if (!Number.isFinite(parsed) || parsed <= 0 || parsed > 1000000) return 'Diện tích không hợp lệ hoặc vượt giới hạn';
    return '';
  }

  if (field === 'contactName') {
    if (!value || !String(value).trim()) return 'Tên người liên hệ không được để trống';
    if (!/^[\p{L}\s'.-]+$/u.test(String(value).trim())) return 'Tên người liên hệ không hợp lệ';
    return '';
  }

  if (field === 'contactPhone') {
    if (!value) return 'Số điện thoại người liên hệ không được để trống';
    if (!/^0[0-9]{9}$/.test(value)) return 'Số điện thoại không đúng định dạng';
    return '';
  }

  if (field === 'contactEmail') {
    if (!value || !String(value).trim()) return 'Email không được để trống';
    const email = String(value).trim().toLowerCase();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Email không hợp lệ';
    return '';
  }

  if (typeof value === 'string' && !value.trim()) return 'Dữ liệu không hợp lệ';
  if (!value) return 'Dữ liệu không hợp lệ';
  return '';
}

function preventNegative(event, field) {
  const val = Number(event.target.value);
  if (val < 0) {
    form[field] = 0;
    event.target.value = 0;
  }
}

function onNumberInput(event, field, allowDecimal = false) {
  let value = event.target.value;
  
  // Xóa toàn bộ ký tự không phải số (và dấu . nếu cho thập phân)
  if (allowDecimal) {
    value = value.replace(/[^0-9.]/g, '');
    // Chỉ giữ 1 dấu . (nếu có nhiều hơn, xóa các dấu . sau)
    const parts = value.split('.');
    if (parts.length > 2) {
      value = parts[0] + '.' + parts.slice(1).join('');
    }
  } else {
    value = value.replace(/[^0-9]/g, '');
  }
  
  form[field] = value;
  event.target.value = value;
}

function onPhoneInput(event) {
  const digits = event.target.value.replace(/[^0-9]/g, '');
  // Giới hạn ở 10 chữ số
  const limited = digits.slice(0, 10);
  form.contactPhone = limited;
  event.target.value = limited;
}

function touchAllRequired() {
  const required = ['images', 'title', 'description', 'propertyType', 'area', 'price', 'provinceCode', 'wardCode', 'streetCode', 'addressDetail', 'contactName', 'contactPhone', 'contactEmail'];
  if (form.demandType === 'RENT') {
    required.push('rentMinTerm', 'rentPaymentInterval');
  }
  required.forEach((f) => touchField(f));
}

function hasRequiredErrors() {
  const base = ['images', 'title', 'description', 'propertyType', 'area', 'provinceCode', 'wardCode', 'streetCode', 'addressDetail', 'contactName', 'contactPhone', 'contactEmail'];
  if (form.demandType === 'RENT') base.push('rentMinTerm', 'rentPaymentInterval');

  const hasError = base.some((f) => {
    touchedFields[f] = true;
    return fieldError(f);
  });

  return hasError || (!form.isNegotiable && fieldError('price'));
}

function setFurnitureStatus(status) {
  form.furnitureStatus = status;
}

function toggleAmenity(amenity) {
  if (selectedAmenities.value.includes(amenity)) {
    selectedAmenities.value = selectedAmenities.value.filter((item) => item !== amenity);
    return;
  }
  selectedAmenities.value = [...selectedAmenities.value, amenity];
}

// ── Draft/LocalStorage Helpers ──
const DRAFT_STORAGE_KEY = "postListing_draft";
const DRAFT_TTL_MS = 60 * 1000; // 1 minute
let draftSaveTimer = null;

function saveFormToDraft() {
  // Chỉ lưu draft khi ở form đăng tin, không lưu khi ở form sửa
  if (isEditMode.value) return;

  clearTimeout(draftSaveTimer);
  draftSaveTimer = setTimeout(() => {
    const draftData = {
      form: { ...form },
      selectedAmenities: [...selectedAmenities.value],
      publicInfoAgreed: publicInfoAgreed.value,
      legalPaperTypesSelection: [...form.legalPaperTypes],
      timestamp: Date.now(),
    };
    localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draftData));
  }, 1000); // Debounce 1 giây
}

function loadFormFromDraft() {
  const savedDraft = localStorage.getItem(DRAFT_STORAGE_KEY);
  if (!savedDraft) return;

  try {
    const draftData = JSON.parse(savedDraft);

    const savedAt = Number(draftData?.timestamp || 0);
    const isExpired = !savedAt || Date.now() - savedAt > DRAFT_TTL_MS;
    if (isExpired) {
      clearDraft();
      return;
    }

    // Restore form data (nhưng không restore files/images vì browser security)
    Object.assign(form, draftData.form);
    
    // Đảm bảo clear các file bị biến thành object rỗng do JSON.stringify
    form.images = [];
    form.video = null;
    form.identityCardFront = null;
    form.identityCardBack = null;
    form.legalDocuments = [];
    
    selectedAmenities.value = draftData.selectedAmenities || [];
    publicInfoAgreed.value = draftData.publicInfoAgreed || false;
    console.log("✓ Form đã được khôi phục từ bản dự thảo");
  } catch (error) {
    console.error("Lỗi khi load dự thảo:", error);
    clearDraft();
  }
}

function clearDraft() {
  localStorage.removeItem(DRAFT_STORAGE_KEY);
}

function pillClass(active) {
  return active
    ? "rounded-full border border-sky-300 bg-white px-4 py-1.5 text-xs font-semibold text-sky-700 shadow-sm"
    : "rounded-full border border-slate-200 bg-white px-4 py-1.5 text-xs text-slate-600 hover:bg-slate-50";
}

function quickChipClass(active) {
  return active
    ? "quick-chip quick-chip-active"
    : "quick-chip";
}

function clearImagePreviews() {
  imagePreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
  imagePreviews.value = [];
}

function clearIdCardPreviews() {
  if (frontCardPreviewUrl.value && String(frontCardPreviewUrl.value).startsWith('blob:')) {
    URL.revokeObjectURL(frontCardPreviewUrl.value);
    frontCardPreviewUrl.value = "";
  }
  if (backCardPreviewUrl.value && String(backCardPreviewUrl.value).startsWith('blob:')) {
    URL.revokeObjectURL(backCardPreviewUrl.value);
    backCardPreviewUrl.value = "";
  }
}

function clearLegalDocumentPreviews() {
  legalDocumentPreviews.value.forEach((preview) => {
    if (preview?.url && String(preview.url).startsWith('blob:')) {
      URL.revokeObjectURL(preview.url);
    }
  });
  legalDocumentPreviews.value = [];
}

function resetForm() {
  clearImagePreviews();
  clearIdCardPreviews();
  clearLegalDocumentPreviews();
  Object.assign(form, createInitialState());
  showLegalDropdown.value = false;
  showMoreDetail.value = true;
  showVerificationSection.value = true;
  publicInfoAgreed.value = false;
  selectedAmenities.value = [];
  submitError.value = "";
  validationErrors.value = {};
  imageUploadError.value = "";
  videoUploadError.value = "";
  verificationUploadError.value = "";
  videoPreviewName.value = "";


}

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
    locationMarker = null;
  }
  clearImagePreviews();
  clearIdCardPreviews();
});

async function submitListing() {
  if (loading.value) {
    pushToast('Bạn đã nhấn đăng tin quá nhanh', 'warning');
    return;
  }

  // Validate required fields first
  touchAllRequired();
  if (hasRequiredErrors()) {
    submitError.value = "";
    return;
  }

  loading.value = true;
  submitError.value = "";
  validationErrors.value = {};
  pushToast('Đang xử lý dữ liệu...', 'info');
  normalizeFormTextFields();
  form.requestVerification = shouldRequestVerification.value;
  form.amenities = [...selectedAmenities.value];
  form.publicInfoAgreed = publicInfoAgreed.value;
  // Thu thập dữ liệu từ AppointmentSlotsForm (chỉ validate nếu có dữ liệu để lưu)
  if (appointmentForm.value) {
    const slots = appointmentForm.value.getFormData();
    if (Array.isArray(slots) && slots.length > 0) {
      const isValidAppointments = appointmentForm.value.validateAll();
      if (!isValidAppointments) {
        loading.value = false;
        submitError.value = 'Lịch hẹn xem nhà không hợp lệ';
        pushToast('Lịch hẹn xem nhà không hợp lệ', 'error');
        return;
      }
      form.appointment_slots = slots;
    } else {
      // No slots provided by user
      form.appointment_slots = [];
    }
  }

  try {
    // Helper function to upload an array of files
    const uploadMultiple = async (files, mode = 'image') => {
      const urls = [];
      for (const file of files) {
        if (typeof file === 'string') {
          urls.push(file); // Already a URL
        } else {
          if (mode === 'image') {
            pushToast('Đang tải lên hình ảnh...', 'info', 1200);
          }
          const res = await cloudinaryService.uploadImage(file, 'listing');
          urls.push(res.secure_url);
        }
      }
      return urls;
    };

    // Helper function to upload a single file
    const uploadSingle = async (file, mode = 'image') => {
      if (!file) return null;
      if (typeof file === 'string') return file;
      if (mode === 'video') {
        pushToast('Đang tải lên video...', 'info', 1200);
      }
      const res = await cloudinaryService.uploadImage(file, 'listing');
      return res.secure_url;
    };

    // 1. Upload Images
    if (form.images && form.images.length > 0) {
      form.images = await uploadMultiple(form.images, 'image');
    }

    // 2. Upload Video
    if (form.video) {
      form.video = await uploadSingle(form.video, 'video');
    }

    // 3. Upload Verification Documents
    if (form.requestVerification) {
      if (form.identityCardFront) {
        form.identityCardFront = await uploadSingle(form.identityCardFront, 'image');
      }
      if (form.identityCardBack) {
        form.identityCardBack = await uploadSingle(form.identityCardBack, 'image');
      }
      if (form.legalDocuments && form.legalDocuments.length > 0) {
        form.legalDocuments = await uploadMultiple(form.legalDocuments, 'image');
      }
    }

    // 4. Submit to Backend
    console.log('Submitting listing payload', JSON.parse(JSON.stringify(form)));
    let response;
    if (isEditMode.value) {
      response = await listingService.update(editListingId.value, form);
    } else {
      response = await listingService.create(form);
    }

    // Ensure appointment slots are saved via dedicated endpoint (replace existing)
    try {
      const listingId = isEditMode.value ? editListingId.value : response.data?.data?.id;
      const slotsPayload = form.appointment_slots || [];
      console.log('Appointment slots payload for listing', listingId, slotsPayload);
      if (listingId !== undefined && listingId !== null) {
        await listingService.replaceAppointmentSlots(listingId, slotsPayload);
      }
    } catch (slotErr) {
      console.error('Failed to save appointment slots:', slotErr);
      // don't block overall success, but notify user
      pushToast('Lưu khung giờ xem nhà thất bại (không ảnh hưởng tới tin đăng)', 'warning');
    }

    submitError.value = "";
    clearDraft();
    pushToast(response.data?.message || (isEditMode.value ? 'Cập nhật tin thành công' : 'Đăng tin thành công. Tin đăng chờ duyệt'), 'success');
    resetForm();
    // Redirect đến trang danh sách tin đăng
    router.push('/profile?tab=listings');
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      const statusCode = error.response.status;
      validationErrors.value = data?.errors || {};

      if (statusCode === 401) {
        submitError.value = 'Phiên làm việc đã hết hạn';
      } else if (statusCode === 500) {
        submitError.value = 'Đã xảy ra lỗi hệ thống';
      } else if (statusCode >= 400 && statusCode < 500) {
        submitError.value = data?.message || 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại';
      } else {
        submitError.value = data?.message || 'Không thể lưu tin đăng. Vui lòng thử lại';
      }

      pushToast(submitError.value, 'error');
    } else {
      submitError.value = error.message || 'Upload thất bại. Vui lòng thử lại';
      pushToast('Kết nối mạng không ổn định', 'error');
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.toast-stack {
  position: fixed;
  top: 88px;
  right: 14px;
  z-index: 1300;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.toast-item {
  min-width: 260px;
  max-width: 360px;
  border-radius: 10px;
  border: 1px solid transparent;
  padding: 10px 12px;
  font-size: 12px;
  font-weight: 600;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
}

.toast-success {
  background: #ecfdf3;
  color: #047857;
  border-color: #a7f3d0;
}

.toast-info {
  background: #eff6ff;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.toast-warning {
  background: #fffbeb;
  color: #b45309;
  border-color: #fde68a;
}

.toast-error {
  background: #fef2f2;
  color: #b91c1c;
  border-color: #fecaca;
}

.section-card {
  border: 1px solid #e6edf5;
  border-radius: 14px;
  background: #fff;
  padding: 14px;
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

.appointment-header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.appointment-header h2 {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
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

.tip-box {
  margin-top: 6px;
  border-radius: 12px;
  background: #f2f6fb;
  border: 1px solid #e5edf6;
  padding: 10px 12px;
}

.verify-note-box {
  border-radius: 16px;
  border: 1px solid #fde6b8;
  background: linear-gradient(180deg, #fff8e8 0%, #fffdf6 100%);
  padding: 14px;
}

.verify-label-row {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
}

.public-info-box {
  display: block;
  border: 1px solid #e1ecf7;
  border-radius: 14px;
  background: #f8fbff;
  padding: 12px;
}

.field-label {
  font-size: 11px;
  font-weight: 600;
  color: #334155;
}

.field-label.required::after {
  content: ' *';
  color: #ef4444;
  font-weight: 700;
}

.with-icon {
  display: inline-flex;
  align-items: center;
  gap: 6px;
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

.search-btn {
  border-radius: 8px;
  border: 1px solid #7cc5f3;
  background: #ffffff;
  color: #0284c7;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.search-btn:hover {
  background: #f0f9ff;
}

.location-map {
  width: 100%;
  height: 290px;
  z-index: 1;
}

.legal-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.dropdown-arrow-icon {
  margin-left: auto;
  display: inline-block;
  width: 9px;
  height: 9px;
  border-right: 2px solid #0f172a;
  border-bottom: 2px solid #0f172a;
  transform: rotate(45deg);
  transform-origin: center;
  flex: 0 0 auto;
}

.legal-selected-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.legal-dropdown {
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid #c9dbf0;
  border-radius: 10px;
  background: #fff;
}

.legal-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  font-size: 13px;
  color: #0f172a;
  border-bottom: 1px solid #e6edf5;
}

.legal-option:last-child {
  border-bottom: none;
}

.quick-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.quick-chip {
  min-width: 38px;
  height: 38px;
  padding: 0 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #f2f5f9;
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
}

.quick-chip-active {
  background: #0ea5e9;
  border-color: #0ea5e9;
  color: #ffffff;
}

.quick-input {
  width: 110px;
  height: 38px;
  border: 1px solid #e1eaf4;
  border-radius: 10px;
  background: #f3f7fc;
  padding: 0 12px;
  font-size: 14px;
  color: #0f172a;
}

.quick-input:focus {
  outline: none;
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
}

.input[type="number"]::-webkit-outer-spin-button,
.input[type="number"]::-webkit-inner-spin-button,
.quick-input[type="number"]::-webkit-outer-spin-button,
.quick-input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.input[type="number"],
.quick-input[type="number"] {
  -moz-appearance: textfield;
}

.unit-input {
  display: flex;
  align-items: center;
  gap: 8px;
}

.unit-label {
  font-size: 18px;
  color: #64748b;
  font-weight: 500;
}

.more-info-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
}

.more-info-badge {
  font-size: 13px;
  color: #0ea5e9;
  background: #e6f6fe;
  border-radius: 999px;
  padding: 4px 10px;
}

.amenity-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid #cbd5e1;
  color: #334155;
  font-size: 14px;
  font-weight: 500;
  background: #ffffff;
}

.amenity-chip.active {
  border-color: #0ea5e9;
  color: #0369a1;
  background: #f0f9ff;
}

.input:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
  background: #fff;
}

.input-error {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.field-error {
  margin-top: 4px;
  font-size: 11px;
  color: #ef4444;
  font-weight: 500;
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

.file-box-inner {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 10px;
}

.preview-card {
  position: relative;
  border: 1px solid #d6e6f7;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fbff;
}

.preview-image {
  width: 100%;
  height: 86px;
  object-fit: cover;
  display: block;
}

.preview-name {
  padding: 6px 8px;
  font-size: 10px;
  line-height: 1.35;
  color: #475569;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.id-preview {
  width: 100%;
  max-width: 120px;
  height: 58px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #d6e6f7;
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

.score-value-wrap {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.score-row-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 999px;
  border: none;
  background: transparent;
  color: #64748b;
}

.score-row-toggle:hover {
  background: #eef6ff;
}

.score-row-toggle-icon {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-right: 2px solid #0f172a;
  border-bottom: 2px solid #0f172a;
  transform: rotate(45deg);
  transform-origin: center;
  transition: transform 0.2s ease;
}

.score-row-toggle-icon.collapsed {
  transform: rotate(-45deg);
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
