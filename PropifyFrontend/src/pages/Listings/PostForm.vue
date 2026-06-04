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
      <Breadcrumb :crumbs="[
        { label: 'Trang chủ', to: '/' },
        { label: pageBreadcrumb }
      ]" />
      <h1 class="mt-2 text-[24px] font-extrabold tracking-tight text-slate-900">{{ pageTitle }}</h1>

      <div :class="['mt-5 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,760px)_330px] lg:justify-center', loading && 'form-interaction-locked']">
      <form class="space-y-4 lg:w-[760px]" @submit.prevent="isVerificationOnlyMode ? submitVerificationOnly() : submitListing()">
        <template v-if="!isVerificationOnlyMode">
        <section class="section-card" data-score-section="media">
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

        <section class="section-card" data-score-section="info">
          <header class="section-title">
            <img :src="homeImageIcon" alt="info" class="h-5 w-5" />
            <h2>Thông tin bất động sản</h2>
          </header>

          <div class="mt-3">
            <p class="field-label required">Nhu cầu của bạn</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="option in demandTypeOptions"
                :key="option.value"
                type="button"
                :class="pillClass(form.demandType === option.value)"
                @click="form.demandType = option.value"
              >
                {{ option.label }}
              </button>
            </div>
          </div>

          <label class="mt-3 block">
            <span class="field-label required">Tên bất động sản</span>
            <input v-model="form.title" :class="['input mt-1', fieldError('title') && 'input-error']" maxlength="120" placeholder="Nhập tên bất động sản" @blur="handleTextBlur('title')" />
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
            <label class="legal-field">
              <span class="field-label">Giấy tờ pháp lý</span>
              <button type="button" class="input mt-1 legal-trigger" @click="toggleLegalDropdown" ref="legalTriggerRef">
                <span v-if="!form.legalPaperTypes.length" class="text-slate-400">Chọn giấy tờ pháp lý</span>
                <span v-else class="legal-selected-text">{{ selectedLegalPaperLabels }}</span>
                <span class="dropdown-arrow-icon" aria-hidden="true"></span>
              </button>
              <div v-if="showLegalDropdown" class="legal-dropdown" ref="legalDropdownRef">
                <label
                  v-for="option in legalPaperOptions"
                  :key="option.value"
                  class="legal-option"
                  :class="{ selected: form.legalPaperTypes.includes(option.value) }"
                >
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
            <label class="price-field">
              <span class="field-label required">{{ priceLabel }}</span>
              <div class="relative mt-1">
                <input
                  v-model="form.price"
                  :class="['input pr-10 disabled:bg-slate-100', fieldError('price') && 'input-error']"
                  :disabled="form.isNegotiable"
                  type="text"
                  inputmode="numeric"
                  placeholder="Nhập số"
                  @input="handlePriceInput"
                  @focus="priceFocused = true"
                  @blur="handlePriceBlur"
                />
                <span v-if="fieldError('price')" class="price-error-icon" aria-hidden="true">!</span>
              </div>
              <p v-if="fieldError('price')" class="field-error">{{ fieldErrorMessage('price') }}</p>
              <div v-if="showPriceSuggestions" class="price-suggestion-panel">
                <button
                  v-for="suggestion in priceSuggestions"
                  :key="suggestion.value"
                  type="button"
                  class="price-suggestion-item"
                  @mousedown.prevent="selectPriceSuggestion(suggestion.value)"
                >
                  {{ suggestion.label }}
                </button>
              </div>
            </label>
          </div>

          <div class="mt-3 flex items-center gap-2">
            <input
              id="is-negotiable-info"
              v-model="form.isNegotiable"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="hasPriceValue"
              @change="handleNegotiableChange"
            />
            <label for="is-negotiable-info" class="text-[14px] text-slate-500">Giá Thương lượng</label>
          </div>

          <div v-if="form.demandType === 'RENT'" class="mt-3 space-y-3">
            <div>
              <p class="field-label">Thời gian thuê tối thiểu</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button
                  v-for="option in rentMinTermOptions"
                  :key="option.value"
                  type="button"
                  :class="pillClass(form.rentMinTerm === option.value)"
                  @click="form.rentMinTerm = option.value"
                >
                  {{ option.label }}
                </button>
              </div>
              <p v-if="fieldError('rentMinTerm')" class="field-error mt-2">{{ fieldErrorMessage('rentMinTerm') }}</p>
            </div>

            <div>
              <p class="field-label">Kỳ thanh toán</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button
                  v-for="option in rentPaymentIntervalOptions"
                  :key="option.value"
                  type="button"
                  :class="pillClass(form.rentPaymentInterval === option.value)"
                  @click="form.rentPaymentInterval = option.value"
                >
                  {{ option.label }}
                </button>
              </div>
              <p v-if="fieldError('rentPaymentInterval')" class="field-error mt-2">{{ fieldErrorMessage('rentPaymentInterval') }}</p>
            </div>

            <div>
              <p class="field-label">Đặt cọc</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <button
                  v-for="option in rentDepositOptions"
                  :key="option.value"
                  type="button"
                  :class="pillClass(form.rentDeposit === option.value)"
                  @click="form.rentDeposit = option.value"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>
          </div>
        </section>

        <section class="section-card" data-score-section="location">
          <header class="section-title">
            <img :src="locationImageIcon" alt="location" class="h-5 w-5" />
            <h2>Vị trí</h2>
          </header>

          <label class="mt-3 block">
            <span class="field-label">Tìm kiếm địa chỉ bất động sản</span>
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
          <div class="relative mt-2 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
            <button
              type="button"
              class="absolute left-3 top-3 z-10 rounded-lg border border-white/70 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-sky-700"
              @click.stop="toggleMapMode"
            >
              {{ mapMode === 'satellite' ? 'Bản đồ' : 'Vệ tinh' }}
            </button>
            <button
              type="button"
              class="absolute left-3 top-[52px] z-10 rounded-lg border border-white/70 px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur transition hover:bg-white"
              :class="isMap3dEnabled ? 'bg-sky-500/95 text-white hover:text-white' : 'bg-white/95 text-slate-700 hover:text-sky-700'"
              @click.stop="toggleMap3d"
            >
              {{ isMap3dEnabled ? '2D' : '3D' }}
            </button>
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

        <section class="section-card" data-score-section="detail">
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
                <input
                  v-model="form.floorNumber"
                  :class="['input mt-2', fieldError('floorNumber') && 'input-error']"
                  type="text"
                  inputmode="numeric"
                  placeholder="Nhập số"
                  @input="onNumberInput($event, 'floorNumber', false)"
                  @blur="touchField('floorNumber')"
                />
                <p v-if="fieldError('floorNumber')" class="field-error">{{ fieldErrorMessage('floorNumber') }}</p>
              </label>
              <label>
                <span class="field-label">Số tầng</span>
                <input
                  v-model="form.floors"
                  :class="['input mt-2', fieldError('floors') && 'input-error']"
                  type="text"
                  inputmode="numeric"
                  placeholder="Nhập số"
                  @input="onNumberInput($event, 'floors', false)"
                  @blur="touchField('floors')"
                />
                <p v-if="fieldError('floors')" class="field-error">{{ fieldErrorMessage('floors') }}</p>
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
              <button
                v-for="option in furnitureStatusOptions"
                :key="option.value"
                type="button"
                :class="pillClass(form.furnitureStatus === option.value)"
                @click="setFurnitureStatus(option.value)"
              >
                {{ option.label }}
              </button>
            </div>

            </div>

          <div class="mt-4">
            <p class="field-label">Tiện ích</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="amenity in amenityOptions"
                :key="amenity.value"
                type="button"
                class="amenity-chip"
                :class="{ active: selectedAmenities.includes(amenity.value) }"
                @click="toggleAmenity(amenity.value)"
              >
                <span>+</span>
                <span>{{ amenity.label }}</span>
              </button>
            </div>
          </div>

        </section>

        <section class="section-card" data-score-section="contact">
          <header class="section-title">
            <img :src="informationImageIcon" alt="contact" class="h-5 w-5" />
            <h2>Thông tin liên hệ</h2>
          </header>

          <div class="contact-action-row mt-3">
            <div class="flex gap-2">
              <button
                v-for="option in posterTypeOptions"
                :key="option.value"
                type="button"
                :class="pillClass(form.posterType === option.value)"
                @click="form.posterType = option.value"
              >
                {{ option.label }}
              </button>
            </div>
            <button type="button" :class="pillClass(false)" @click="useAccountContactInfo">
              Dùng thông tin tài khoản
            </button>
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
        </template>

        <section v-if="form.demandType !== 'RENT'" class="section-card">
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
              <span>CCCD/CMND chủ sở hữu</span>
              <img :src="infoDotIcon" alt="info" class="h-4 w-4 opacity-70" />
            </p>

            <div class="grid grid-cols-2 gap-3">
              <label class="file-box group">
                <div v-if="frontCardPreviewUrl" class="file-box-inner">
                  <img :src="frontCardPreviewUrl" alt="CCCD mặt trước" class="id-preview" />
                  <span class="text-xs text-slate-500">Mặt trước</span>
                  <button
                    type="button"
                    class="verification-image-remove"
                    title="Xóa ảnh CCCD mặt trước"
                    aria-label="Xóa ảnh CCCD mặt trước"
                    @click.stop.prevent="removeFrontCard"
                  >×</button>
                </div>
                <div v-else class="file-box-inner">
                  <img :src="plusImageIcon" alt="plus" class="h-6 w-6 opacity-70" />
                  <span class="text-xs text-slate-500">Mặt trước</span>
                </div>
                <input class="hidden" type="file" accept="image/*" @change="onFrontCardChange" />
              </label>
              <label class="file-box group">
                <div v-if="backCardPreviewUrl" class="file-box-inner">
                  <img :src="backCardPreviewUrl" alt="CCCD mặt sau" class="id-preview" />
                  <span class="text-xs text-slate-500">Mặt sau</span>
                  <button
                    type="button"
                    class="verification-image-remove"
                    title="Xóa ảnh CCCD mặt sau"
                    aria-label="Xóa ảnh CCCD mặt sau"
                    @click.stop.prevent="removeBackCard"
                  >×</button>
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
                <span>Giấy tờ pháp lý</span>
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
          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              :disabled="formBusy"
              class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-sky-300 hover:text-sky-600 disabled:cursor-not-allowed disabled:opacity-60"
              @click="openBackConfirm"
            >
              Quay lại
            </button>
            <button
              type="button"
              :disabled="formBusy"
              class="rounded-xl border border-sky-200 bg-sky-50 px-5 py-2.5 text-sm font-semibold text-sky-600 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-60"
              @click="openPreview"
            >
              Xem trước tin đăng
            </button>
            <button type="submit" :disabled="!canSubmitListing" class="ml-auto rounded-xl bg-sky-500 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60">
              {{ submitButtonText }}
            </button>
          </div>
        </div>
      </form>

      <div v-if="showPreviewModal" class="preview-overlay" @click.self="showPreviewModal = false">
        <section class="preview-modal preview-modal-detail">
          <header class="preview-header">
            <div>
              <h2>Trang chi tiết tin đăng</h2>
              <p class="mt-1 text-xs text-slate-500">Xem trước bằng giao diện trang chi tiết tin đăng thật</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-sky-300 hover:text-sky-600"
                @click="showPreviewModal = false"
              >
                Quay lại chỉnh sửa
              </button>
            </div>
          </header>
          <div class="preview-detail-body">
            <div class="preview-static-frame">
              <ListingDetail preview-mode :preview-listing="previewListing" />
            </div>
          </div>
        </section>
      </div>

      <div v-if="showDraftConfirm" class="preview-overlay" @click.self="showDraftConfirm = false">
        <section class="draft-confirm-modal">
          <h2 class="text-lg font-bold text-slate-900">Lưu tin dưới dạng nháp?</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600">Bạn có muốn lưu tin hiện tại vào database với trạng thái nháp trước khi quay lại không?</p>
          <div class="mt-5 flex flex-wrap justify-end gap-3">
            <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="discardAndGoBack">Không lưu</button>
            <button type="button" class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-600 disabled:opacity-60" :disabled="savingDraft" @click="saveDraftAndGoBack">
              {{ savingDraft ? 'Đang lưu...' : 'Đồng ý lưu nháp' }}
            </button>
          </div>
        </section>
      </div>

      <aside class="hidden lg:block">
        <div class="sticky top-24 space-y-4">

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-[28px] font-extrabold leading-none" :class="scoreLevel.color">{{ formatScorePoint(totalScore) }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-800">{{ scoreLevel.title }}</p>
            <p class="text-sm text-slate-400">{{ scoreLevel.description }}</p>
            <p class="mt-2 text-xs font-semibold text-slate-400">Điểm tối thiểu: {{ minimumScore }}đ</p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <h3 class="text-[28px] font-extrabold leading-none text-slate-900">Chi tiết điểm thông tin</h3>

            <div class="mt-4 space-y-3">
              <div class="score-row" :class="{ 'score-row-active': activeScoreSection === 'media' }">
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
                  <div :class="imageDone ? 'text-emerald-600' : ''">• Hình ảnh</div>
                  <div :class="imageDone ? 'text-emerald-600' : 'text-slate-400'">{{ imageDone ? '✓' : '' }} {{ formatScorePoint(imagePoints) }}đ · {{ imageScoreCount }}/4</div>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                  <div :class="videoPresent ? 'text-emerald-600' : ''">• Video</div>
                  <div :class="videoPresent ? 'text-emerald-600' : 'text-slate-400'">{{ videoPresent ? '✓' : '' }} 2đ</div>
                </div>
              </div>

              <div class="score-row" :class="{ 'score-row-active': activeScoreSection === 'info' || activeScoreSection === 'location' }">
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
                  class="score-item-row flex items-center justify-between text-sm mt-1"
                  :class="{ 'score-item-active': item.section === activeScoreSection }"
                >
                  <div :class="item.done ? 'text-emerald-600' : ''">• {{ item.label }}</div>
                  <div :class="item.done ? 'text-emerald-600' : 'text-slate-400'">{{ item.done ? '✓' : '' }} {{ item.points }}đ</div>
                </div>
              </div>

              <div class="score-row" :class="{ 'score-row-active': activeScoreSection === 'detail' }">
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
                  <div :class="item.done ? 'text-emerald-600' : 'text-slate-400'">{{ item.done ? '✓' : '' }} {{ item.points }}đ</div>
                </div>
              </div>

              <div class="score-row" :class="{ 'score-row-active': activeScoreSection === 'contact' }">
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
                  <div :class="item.done ? 'text-emerald-600' : 'text-slate-400'">{{ item.done ? '✓' : '' }} {{ item.points }}đ</div>
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
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import maplibregl from "maplibre-gl";
import "maplibre-gl/dist/maplibre-gl.css";
import listingService from "@/services/listingService";
import { buildListingPreview } from "@/services/listingPreviewBuilder";
import {
  MAX_LISTING_VIDEO_SIZE_BYTES,
  MAX_LISTING_VIDEO_SIZE_LABEL,
  useListingMediaUpload,
} from "@/composables/useListingMediaUpload";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import AppointmentSlotsForm from "@/components/appointments/AppointmentSlotsForm.vue";
import ListingDetail from "@/pages/Listings/Detail.vue";
import realEstateLightStyle from "@/assets/maps/real-estate-light.json";

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

function createEmptyPostingOptions() {
  return {
    demand_types: [],
    property_types: {
      sale: [],
      rent: [],
      legacy: [],
    },
    legal_paper_types: [],
    quick_numbers: [],
    amenities: [],
    directions: [],
    furniture_statuses: [],
    poster_types: [],
    rental: {
      min_terms: [],
      payment_intervals: [],
      deposits: [],
    },
  };
}

function createInitialState() {
  return {
    demandType: "",
    title: "",
    description: "",
    propertyType: "",
    provinceCode: "",
    province: "",
    districtCode: "",
    wardCode: "",
    ward: "",
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
    posterType: "",
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
const authStore = useAuthStore();
const isEditMode = computed(() => !!route.params.id);
const editListingId = computed(() => route.params.id);
const isVerificationOnlyMode = computed(() => isEditMode.value && route.query.mode === 'verification');
const pageBreadcrumb = computed(() => {
  if (isVerificationOnlyMode.value) return 'Xác thực bất động sản';
  return isEditMode.value ? 'Chỉnh sửa tin' : 'Đăng tin';
});
const pageTitle = computed(() => {
  if (isVerificationOnlyMode.value) return 'Xác thực bất động sản';
  return isEditMode.value ? 'Chỉnh sửa tin đăng' : 'Đăng tin bất động sản';
});
const submitButtonLabel = computed(() => (isVerificationOnlyMode.value ? 'Gửi thông tin xác thực' : 'Tiếp tục'));
const submitLoadingLabel = computed(() => (isVerificationOnlyMode.value ? 'Đang lưu xác thực...' : 'Đang đăng tin...'));
const isHydratingEdit = ref(false);
const isSyncingAdminFromMap = ref(false);
const initialEditSnapshot = ref("");
const editListingStatus = ref("");
const editLoading = ref(false);
const loading = ref(false);
const savingDraft = ref(false);
const showPreviewModal = ref(false);
const showDraftConfirm = ref(false);
const submitError = ref("");
const validationErrors = ref({});
let submitErrorTimer = null;
const touchedFields = reactive({});
const locationSearchText = ref("");
const mapElement = ref(null);
const mapMode = ref("standard");
const isMap3dEnabled = ref(false);
let map = null;
const POSTING_LOCATION_SOURCE_ID = "property";
const POSTING_LOCATION_FEATURE_ID = "posting-location";
const SATELLITE_LAYER_ID = "satellite-base";
const SATELLITE_SOURCE_ID = "satellite";
const STANDARD_MAP_PITCH = 38;
const TOP_DOWN_MAP_PITCH = 0;
const SATELLITE_HIDDEN_BASE_LAYERS = [
  "landcover-park",
  "landuse-public",
  "water",
  "waterway",
  "aeroway",
  "building-footprints",
  "building-3d",
  "road-minor-casing",
  "road-minor",
  "road-major-casing",
  "road-major",
  "rail-transit",
  "place-label-city",
  "road-label",
];
let lastStandardCamera = null;
const provinces = ref([]);
const wards = ref([]);
const wardsLoading = ref(false);
const postingOptions = ref(createEmptyPostingOptions());

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
const activeScoreSection = ref('media');
const priceFocused = ref(false);
let scoreSectionObserver = null;

const toasts = ref([]);
let toastIdCounter = 1;
const MAX_VIDEO_SIZE_BYTES = MAX_LISTING_VIDEO_SIZE_BYTES;
const MAX_VIDEO_SIZE_LABEL = MAX_LISTING_VIDEO_SIZE_LABEL;

function pushToast(message, type = "info", duration = 2500) {
  const id = toastIdCounter++;
  toasts.value = [...toasts.value, { id, message, type }];
  setTimeout(() => {
    toasts.value = toasts.value.filter((item) => item.id !== id);
  }, duration);
}

function clearSubmitError() {
  if (submitErrorTimer) {
    clearTimeout(submitErrorTimer);
    submitErrorTimer = null;
  }
  submitError.value = "";
}

function setSubmitError(message, duration = 5000) {
  clearSubmitError();
  submitError.value = message || "";
  if (!submitError.value) return;

  submitErrorTimer = setTimeout(() => {
    clearSubmitError();
    submitErrorTimer = null;
  }, duration);
}

const listingMediaUpload = useListingMediaUpload({
  onStatus: (message) => pushToast(message, "info", 1200),
});

function snapshotValue(value) {
  if (value instanceof File) {
    return {
      fileName: value.name,
      fileSize: value.size,
      fileType: value.type,
      lastModified: value.lastModified,
    };
  }

  if (Array.isArray(value)) {
    return value.map((item) => snapshotValue(item));
  }

  if (value && typeof value === "object") {
    return Object.keys(value)
      .sort()
      .reduce((result, key) => {
        result[key] = snapshotValue(value[key]);
        return result;
      }, {});
  }

  return value ?? "";
}

function getAppointmentRowsSnapshot() {
  const normalizeSlot = (slot) => ({
    day_of_week: Number(slot.day_of_week),
    start_time: String(slot.start_time || "").slice(0, 8),
    end_time: String(slot.end_time || "").slice(0, 8),
  });
  const sortSlots = (slots) => slots
    .map(normalizeSlot)
    .filter((slot) => Number.isFinite(slot.day_of_week) && slot.start_time && slot.end_time)
    .sort((a, b) =>
      a.day_of_week - b.day_of_week ||
      a.start_time.localeCompare(b.start_time) ||
      a.end_time.localeCompare(b.end_time),
    );

  if (appointmentForm.value?.getFormData) {
    return snapshotValue(sortSlots(appointmentForm.value.getFormData()));
  }

  const pendingSlots = [];
  (pendingAppointmentRows.value || []).forEach((row) => {
    (row.selected_days || []).forEach((day) => {
      pendingSlots.push({
        day_of_week: day,
        start_time: row.start_time,
        end_time: row.end_time,
      });
    });
  });

  return snapshotValue(sortSlots(pendingSlots));
}

function createEditSnapshot() {
  const normalizeFieldForSnapshot = (key, value) => {
    if ((key === "lat" || key === "lng") && value !== "" && value !== null && value !== undefined) {
      const numericValue = Number(value);
      return Number.isFinite(numericValue) ? numericValue.toFixed(7) : value;
    }

    return value;
  };

  const formSnapshot = Object.keys(createInitialState())
    .sort()
    .reduce((result, key) => {
      result[key] = snapshotValue(normalizeFieldForSnapshot(key, form[key]));
      return result;
    }, {});

  return JSON.stringify({
    form: formSnapshot,
    selectedAmenities: snapshotValue([...selectedAmenities.value].sort()),
    publicInfoAgreed: Boolean(publicInfoAgreed.value),
    appointmentSlots: getAppointmentRowsSnapshot(),
  });
}

function captureInitialEditSnapshot() {
  initialEditSnapshot.value = createEditSnapshot();
}

const formBusy = computed(() => loading.value || savingDraft.value);
const isEditDirty = computed(() => {
  if (!isEditMode.value) return true;
  if (isHydratingEdit.value || editLoading.value || !initialEditSnapshot.value) return false;
  return createEditSnapshot() !== initialEditSnapshot.value;
});
const isUnlistedRelistingMode = computed(() => isEditMode.value && editListingStatus.value === 'UNLISTED');
const isRelistingMode = computed(() => isEditMode.value && ['UNLISTED', 'REJECTED'].includes(editListingStatus.value));
const canSubmitListing = computed(() => !formBusy.value && (!isEditMode.value || isUnlistedRelistingMode.value || isEditDirty.value));
const submitButtonText = computed(() => {
  if (savingDraft.value) return 'Đang lưu nháp...';
  if (loading.value) {
    if (isVerificationOnlyMode.value) return 'Đang lưu xác thực...';
    if (isRelistingMode.value) return 'Đang đăng lại tin...';
    return isEditMode.value ? 'Đang cập nhật tin...' : 'Đang đăng tin...';
  }
  if (isVerificationOnlyMode.value) return 'Lưu xác thực';
  if (isRelistingMode.value) return 'Đăng lại tin';
  return isEditMode.value ? 'Cập nhật tin' : 'Đăng tin';
});

const MAX_LISTING_IMAGES = 10;
const IMAGE_SCORE_TARGET_COUNT = 4;

const demandTypeOptions = computed(() => postingOptions.value.demand_types || []);
const salePropertyTypeOptions = computed(() => postingOptions.value.property_types?.sale || []);
const rentPropertyTypeOptions = computed(() => postingOptions.value.property_types?.rent || []);
const legalPaperOptions = computed(() => postingOptions.value.legal_paper_types || []);
const quickNumberOptions = computed(() => postingOptions.value.quick_numbers || []);
const amenityOptions = computed(() => postingOptions.value.amenities || []);
const directionOptions = computed(() => postingOptions.value.directions || []);
const furnitureStatusOptions = computed(() => postingOptions.value.furniture_statuses || []);
const posterTypeOptions = computed(() => postingOptions.value.poster_types || []);
const rentMinTermOptions = computed(() => postingOptions.value.rental?.min_terms || []);
const rentPaymentIntervalOptions = computed(() => postingOptions.value.rental?.payment_intervals || []);
const rentDepositOptions = computed(() => postingOptions.value.rental?.deposits || []);

const imageCount = computed(() => Array.isArray(form.images) ? form.images.length : 0);
const imageScoreCount = computed(() => Math.min(imageCount.value, IMAGE_SCORE_TARGET_COUNT));
const imageDone = computed(() => imageScoreCount.value >= IMAGE_SCORE_TARGET_COUNT);
const videoPresent = computed(() => Boolean(form.video));

const imagePoints = computed(() => Number(((imageScoreCount.value / IMAGE_SCORE_TARGET_COUNT) * 2).toFixed(2)));
const videoPoints = computed(() => (videoPresent.value ? 2 : 0));
const mediaPoints = computed(() => Math.min(imagePoints.value + videoPoints.value, 4));
const mediaPercent = computed(() => Math.round((mediaPoints.value / 4) * 100));
const mediaDone = computed(() => mediaPoints.value === 4);

const infoChecklist = computed(() => [
  { label: 'Nhu cầu', done: Boolean(form.demandType), points: 0.15, section: 'info' },
  { label: 'Tiêu đề', done: Boolean(form.title?.trim()), points: 0.25, section: 'info' },
  { label: 'Mô tả', done: descriptionCount.value >= 20, points: 0.25, section: 'info' },
  { label: 'Loại nhà đất', done: Boolean(form.propertyType?.trim()), points: 0.2, section: 'info' },
  { label: 'Giấy tờ pháp lý', done: Array.isArray(form.legalPaperTypes) && form.legalPaperTypes.length > 0, points: form.demandType === 'RENT' ? 0.1 : 0.15, section: 'info' },
  { label: 'Diện tích', done: Number(form.area) > 0, points: 0.25, section: 'info' },
  { label: form.demandType === 'RENT' ? 'Giá thuê' : 'Giá bán', done: form.isNegotiable || Number(form.price) > 0, points: form.demandType === 'RENT' ? 0.2 : 0.3, section: 'info' },
  ...(form.demandType === 'RENT'
    ? [
        { label: 'Thời gian cho thuê', done: Boolean(form.rentMinTerm), points: 0.05, section: 'info' },
        { label: 'Kỳ thanh toán', done: Boolean(form.rentPaymentInterval), points: 0.05, section: 'info' },
        { label: 'Đặt cọc', done: Boolean(form.rentDeposit), points: 0.05, section: 'info' },
      ]
    : []),
  { label: 'Tỉnh/thành phố', done: Boolean(form.provinceCode?.trim()), points: 0.15, section: 'location' },
  { label: 'Xã/phường', done: Boolean(form.wardCode?.trim()), points: 0.1, section: 'location' },
  { label: 'Đường/phố', done: Boolean(form.streetCode?.trim()), points: 0.1, section: 'location' },
  { label: 'Địa chỉ cụ thể', done: Boolean(form.addressDetail?.trim()), points: 0.1, section: 'location' },
]);

const infoRawPoints = computed(() => scoreItems(infoChecklist.value));
const infoMaxRawPoints = computed(() => maxScoreItems(infoChecklist.value));
const infoPoints = computed(() => normalizeGroupScore(infoRawPoints.value, infoMaxRawPoints.value, 2));
const infoPercent = computed(() => percentScore(infoRawPoints.value, infoMaxRawPoints.value));
const infoDone = computed(() => infoRawPoints.value >= infoMaxRawPoints.value && infoMaxRawPoints.value > 0);

const detailChecklist = computed(() => [
  { label: 'Số phòng ngủ', done: String(form.bedrooms ?? '').trim() !== '', points: 0.3 },
  { label: 'Số phòng tắm', done: String(form.bathrooms ?? '').trim() !== '', points: 0.3 },
  { label: 'Mặt tiền', done: String(form.facadeWidth ?? '').trim() !== '', points: 0.3 },
  { label: 'Chiều sâu', done: String(form.depth ?? '').trim() !== '', points: 0.3 },
  { label: 'Số tầng', done: String(form.floors ?? '').trim() !== '', points: 0.3 },
  { label: 'Tầng thứ', done: String(form.floorNumber ?? '').trim() !== '', points: 0.3 },
  { label: 'Hướng nhà', done: Boolean(form.directionCode), points: 0.3 },
  { label: 'Hướng ban công', done: Boolean(form.balconyDirectionCode), points: 0.2 },
  { label: 'Số ban công', done: String(form.balconies ?? '').trim() !== '', points: 0.2 },
  { label: 'Nội thất', done: Boolean(form.furnitureStatus), points: 0.2 },
  { label: 'Tiện ích', done: Array.isArray(selectedAmenities.value) && selectedAmenities.value.length > 0, points: 0.3 },
]);

const detailRawPoints = computed(() => scoreItems(detailChecklist.value));
const detailMaxRawPoints = computed(() => maxScoreItems(detailChecklist.value));
const detailPoints = computed(() => normalizeGroupScore(detailRawPoints.value, detailMaxRawPoints.value, 3));
const detailPercent = computed(() => percentScore(detailRawPoints.value, detailMaxRawPoints.value));
const detailDone = computed(() => detailRawPoints.value >= detailMaxRawPoints.value && detailMaxRawPoints.value > 0);

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
  { label: 'Đối tượng', done: Boolean(form.posterType), points: 0.2 },
  { label: 'Họ và tên', done: isContactNameValid.value, points: 0.3 },
  { label: 'Số điện thoại', done: isContactPhoneValid.value, points: 0.4 },
  { label: 'Email', done: isContactEmailValid.value, points: 0.1 },
]);

const contactRawPoints = computed(() => scoreItems(contactChecklist.value));
const contactMaxRawPoints = computed(() => maxScoreItems(contactChecklist.value));
const contactPoints = computed(() => normalizeGroupScore(contactRawPoints.value, contactMaxRawPoints.value, 1));
const contactDone = computed(() => contactRawPoints.value >= contactMaxRawPoints.value && contactMaxRawPoints.value > 0);
const contactPercent = computed(() => percentScore(contactRawPoints.value, contactMaxRawPoints.value));

function scoreItems(items) {
  return Number(items.reduce((sum, item) => sum + (item.done ? Number(item.points || 0) : 0), 0).toFixed(2));
}

function maxScoreItems(items) {
  return Number(items.reduce((sum, item) => sum + Number(item.points || 0), 0).toFixed(2));
}

function normalizeGroupScore(value, max, target) {
  if (!max) return 0;
  return Number(Math.min((value / max) * target, target).toFixed(2));
}

function percentScore(value, max) {
  if (!max) return 0;
  return Math.round(Math.min((value / max) * 100, 100));
}

function formatScorePoint(value) {
  return Number(value).toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

const totalScore = computed(() => {
  return Number((mediaPoints.value + infoPoints.value + detailPoints.value + contactPoints.value).toFixed(2));
});

const minimumScore = computed(() => (form.demandType === 'RENT' ? 5.7 : 5.8));
const isBelowMinimumScore = computed(() => totalScore.value < minimumScore.value);

const scoreLevel = computed(() => {
  if (isBelowMinimumScore.value) {
    return {
      title: 'Dưới điểm tối thiểu',
      description: 'Tin chưa đủ điều kiện đăng',
      color: 'text-red-400',
    };
  }

  if (totalScore.value >= 8) {
    return {
      title: 'Thông tin ở mức tốt',
      description: 'Tin có khả năng tiếp cận tốt hơn',
      color: 'text-emerald-500',
    };
  }

  return {
    title: 'Thông tin ở mức tối thiểu',
    description: 'Tin đủ điều kiện đăng',
    color: 'text-sky-500',
  };
});

const selectedLegalPaperLabels = computed(() => {
  return legalPaperOptions.value
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

function setActiveScoreSection(section) {
  if (!section || activeScoreSection.value === section) return;

  activeScoreSection.value = section;
  mediaCollapsed.value = section !== 'media';
  infoCollapsed.value = section !== 'info' && section !== 'location';
  detailCollapsed.value = section !== 'detail';
  contactCollapsed.value = section !== 'contact';
}

function initScoreSectionObserver() {
  scoreSectionObserver?.disconnect();

  if (typeof IntersectionObserver === 'undefined') return;

  const sections = Array.from(document.querySelectorAll('[data-score-section]'));
  if (!sections.length) return;

  scoreSectionObserver = new IntersectionObserver((entries) => {
    const visibleEntries = entries
      .filter((entry) => entry.isIntersecting)
      .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

    const activeEntry = visibleEntries[0];
    const section = activeEntry?.target?.dataset?.scoreSection;
    if (section) setActiveScoreSection(section);
  }, {
    root: null,
    threshold: [0.25, 0.45, 0.65],
    rootMargin: '-32% 0px -42% 0px',
  });

  sections.forEach((section) => scoreSectionObserver.observe(section));
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  initScoreSectionObserver();
});

// If the appointment component wasn't mounted when we loaded edit data,
// assign pending rows once the ref becomes available.
watch(appointmentForm, (v) => {
  if (v && pendingAppointmentRows.value) {
    setAppointmentRows(pendingAppointmentRows.value);
    pendingAppointmentRows.value = null;
    if (isEditMode.value && !isHydratingEdit.value && !initialEditSnapshot.value) {
      nextTick(captureInitialEditSnapshot);
    }
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  scoreSectionObserver?.disconnect();
  scoreSectionObserver = null;
});

const shouldRequestVerification = computed(() => {
  if (form.demandType === 'RENT') return false;

  return Boolean(
    form.identityCardFront
    && form.identityCardBack
    && Array.isArray(form.legalDocuments)
    && form.legalDocuments.length > 0
  );
});

const hasAnyVerificationDocuments = computed(() => Boolean(
  form.identityCardFront
  || form.identityCardBack
  || (Array.isArray(form.legalDocuments) && form.legalDocuments.length > 0)
));

const titleCount = computed(() => normalizeSingleLineText(form.title).length);
const descriptionCount = computed(() => normalizeMultilineText(form.description).length);
const priceLabel = computed(() => (form.demandType === "RENT" ? "Giá thuê" : "Giá bán"));
const hasPriceValue = computed(() => Number(String(form.price || '').replace(/[^0-9]/g, '')) > 0);
const priceSuggestions = computed(() => {
  const base = Number(String(form.price || '').replace(/[^0-9]/g, ''));
  if (!base || form.isNegotiable) return [];

  const multipliers = [100, 1000, 10000];

  return multipliers
    .map((multiplier) => base * multiplier)
    .filter((value, index, arr) => value > 0 && arr.indexOf(value) === index)
    .map((value) => ({
      value,
      label: value.toLocaleString('vi-VN'),
    }));
});

const showPriceSuggestions = computed(() => priceFocused.value && priceSuggestions.value.length > 0);

const currentPropertyTypeOptions = computed(() => {
  const options = form.demandType === "RENT"
    ? rentPropertyTypeOptions.value
    : salePropertyTypeOptions.value;

  if (!isEditMode.value || !form.propertyType || options.some((option) => option.value === form.propertyType)) {
    return options;
  }

  const legacyOption = (postingOptions.value.property_types?.legacy || [])
    .find((option) => option.value === form.propertyType);

  return legacyOption ? [...options, legacyOption] : options;
});

const selectedProvinceName = computed(() => {
  const item = provinces.value.find((province) => String(province.code) === form.provinceCode);
  return item?.name || "";
});

const selectedWardName = computed(() => {
  const item = wards.value.find((ward) => String(ward.code) === form.wardCode);
  return item?.name || "";
});

watch(selectedProvinceName, (name) => {
  form.province = name;
}, { immediate: true });

watch(selectedWardName, (name) => {
  form.ward = name;
}, { immediate: true });

const previewListing = computed(() => buildListingPreview({
  form,
  imagePreviews: imagePreviews.value,
  selectedAmenities: selectedAmenities.value,
  authUser: authStore.user,
  provinceName: selectedProvinceName.value,
  wardName: selectedWardName.value,
}));

watch(
  () => form.demandType,
  (demandType) => {
    ensurePropertyTypeOption();
    if (demandType === 'RENT') {
      clearVerificationData();
    }
  },
);

watch(demandTypeOptions, () => {
  ensureDemandTypeOption();
});

watch(currentPropertyTypeOptions, () => {
  ensurePropertyTypeOption();
});

watch(posterTypeOptions, () => {
  ensurePosterTypeOption();
});

watch(
  () => form.price,
  () => {
    if (hasPriceValue.value && form.isNegotiable) {
      form.isNegotiable = false;
    }
  },
);

watch(
  () => form.isNegotiable,
  (isNegotiable) => {
    if (isNegotiable) {
      clearPriceForNegotiable();
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
  await fetchPostingOptions();
  await fetchProvinces();

  if (isEditMode.value) {
    clearDraft(); // Xóa draft khi vào form sửa
    await loadListingForEdit();
  } else {
    loadFormFromDraft();
  }
});

watch(
  () => [route.name, route.params.id, route.query.mode],
  async () => {
    if (isEditMode.value) {
      resetFormState();
      clearDraft();
      await loadListingForEdit();
      return;
    }

    resetFormState();
    loadFormFromDraft();
  },
);

function getVerificationDocumentType(document) {
  return document?.type || document?.document_type || document?.documentType || '';
}

function getVerificationDocumentUrl(document) {
  return document?.url || document?.file_url || document?.fileUrl || '';
}

async function loadListingForEdit() {
  editLoading.value = true;
  isHydratingEdit.value = true;
  try {
    let response;
    try {
      response = await listingService.getMineById(editListingId.value);
    } catch (ownedError) {
      console.warn('Failed to load owned listing details, fallback to public details:', ownedError);
      response = await listingService.getById(editListingId.value);
    }

    const data = response.data?.data || response.data;
    if (!data || typeof data !== 'object') {
      throw new Error('Dữ liệu tin đăng không hợp lệ');
    }
    clearSubmitError();

    const p = data.property || {};
    editListingStatus.value = data.status || '';
    const inputValue = (value) => (value === null || value === undefined ? '' : String(value));
    const arrayValue = (value) => {
      if (Array.isArray(value)) return value;
      if (typeof value === 'string' && value.trim()) {
        try {
          const parsed = JSON.parse(value);
          return Array.isArray(parsed) ? parsed : [];
        } catch {
          return [];
        }
      }
      return [];
    };
    const mediaUrl = (item, ...keys) => {
      for (const key of keys) {
        if (item?.[key]) return item[key];
      }
      return '';
    };
    const normalizeTimeForInput = (value) => {
      const match = String(value || '').match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?/);
      return match ? `${String(Number(match[1])).padStart(2, '0')}:${match[2]}:${match[3] || '00'}` : '';
    };

    form.demandType = data.demand_type || '';
    form.title = data.title || '';
    form.description = data.description || '';
    form.propertyType = p.type || '';
    form.provinceCode = p.province_code ? String(p.province_code) : '';
    form.province = p.province || p.province_name || '';
    form.districtCode = p.district_code ? String(p.district_code) : '';
    form.wardCode = p.ward_code ? String(p.ward_code) : '';
    form.ward = p.ward || p.ward_name || '';
    form.streetCode = p.street_code || '';
    form.projectName = p.project_name || '';
    form.addressDetail = p.address_detail || '';
    form.area = inputValue(p.area);
    form.isNegotiable = Boolean(p.is_negotiable);
    form.price = form.isNegotiable ? '' : inputValue(p.price);
    form.bedrooms = inputValue(p.bedrooms);
    form.bathrooms = inputValue(p.bathrooms);
    form.floors = inputValue(p.floors);
    form.floorNumber = inputValue(p.floor_number);
    form.balconies = inputValue(p.balconies);
    form.facadeWidth = inputValue(p.facade_width);
    form.depth = inputValue(p.depth);
    form.roadWidth = inputValue(p.road_width);
    form.directionCode = p.direction_code || '';
    form.balconyDirectionCode = p.balcony_direction_code || '';
    form.furnitureStatus = p.furniture_status || '';
    form.legalPaperTypes = arrayValue(p.legal_paper_types);
    form.contactName = p.contact_name || '';
    form.contactPhone = p.contact_phone || '';
    form.contactEmail = p.contact_email || '';
    form.posterType = p.poster_type || '';
    form.lat = p.lat ?? '';
    form.lng = p.lng ?? '';
    form.amenities = arrayValue(p.amenities);
    form.publicInfoAgreed = Boolean(p.public_info_agreed);
    form.rentMinTerm = data.rent_min_term || '';
    form.rentPaymentInterval = data.rent_payment_interval || '';
    form.rentDeposit = data.rent_deposit || '';
    form.packageId = data.package?.id ? String(data.package.id) : inputValue(data.package_id);
    form.requestVerification = Boolean(data.request_verification);
    form.attributeIds = Array.isArray(p.attributes) ? p.attributes.map((attribute) => attribute.id).filter(Boolean) : [];
    selectedAmenities.value = [...arrayValue(p.amenities)];
    publicInfoAgreed.value = Boolean(p.public_info_agreed);
    ensurePostingOptionSelections();

    const verificationDocuments = Array.isArray(data.verification_documents) ? data.verification_documents : [];
    const idFrontDoc = verificationDocuments.find((doc) => getVerificationDocumentType(doc) === 'ID_FRONT');
    const idBackDoc = verificationDocuments.find((doc) => getVerificationDocumentType(doc) === 'ID_BACK');
    const legalDocs = verificationDocuments.filter((doc) => getVerificationDocumentType(doc) === 'LEGAL_DOCUMENT');
    const idFrontUrl = getVerificationDocumentUrl(idFrontDoc);
    const idBackUrl = getVerificationDocumentUrl(idBackDoc);

    frontCardPreviewUrl.value = idFrontUrl;
    backCardPreviewUrl.value = idBackUrl;
    legalDocumentPreviews.value = legalDocs.map((doc, index) => ({
      name: `Giấy tờ pháp lý ${index + 1}`,
      url: getVerificationDocumentUrl(doc),
    })).filter((preview) => preview.url);
    form.identityCardFront = idFrontUrl || null;
    form.identityCardBack = idBackUrl || null;
    form.legalDocuments = legalDocumentPreviews.value.map((preview) => preview.url);
    if (isVerificationOnlyMode.value) {
      showVerificationSection.value = true;
    }

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
        start_time: normalizeTimeForInput(row.start_time),
        end_time: normalizeTimeForInput(row.end_time),
        selected_days: row.selected_days.sort((a, b) => a - b),
      }));
      if (appointmentForm.value) {
        setAppointmentRows(rows);
      } else {
        pendingAppointmentRows.value = rows;
      }
    } else {
      existingAppointmentSlotIds.value = [];
    }



    const verificationImageUrls = verificationDocuments
      .map((doc) => getVerificationDocumentUrl(doc))
      .filter(Boolean);

    // Load existing images as URLs (not File objects)
    if (data.images && data.images.length > 0) {
      const sorted = [...data.images].sort((a, b) => a.sort_order - b.sort_order);
      const existingImages = sorted
        .map((img) => mediaUrl(img, 'url', 'image_url', 'imageUrl'))
        .filter(Boolean);
      form.images = existingImages;
      imagePreviews.value = existingImages.map((url, index) => ({
        name: `Ảnh ${index + 1}`,
        url,
      }));
    } else if (verificationImageUrls.length > 0) {
      form.images = verificationImageUrls;
      imagePreviews.value = verificationImageUrls.map((url, index) => ({
        name: `Ảnh ${index + 1}`,
        url,
      }));
    } else {
      form.images = [];
      imagePreviews.value = [];
    }

    if (Array.isArray(data.videos) && data.videos.length > 0) {
      const firstVideo = data.videos[0];
      const videoUrl = mediaUrl(firstVideo, 'url', 'video_url', 'videoUrl');
      form.video = videoUrl || null;
      videoPreviewName.value = videoUrl ? 'Video hiện tại' : '';
    } else {
      form.video = null;
      videoPreviewName.value = '';
    }

    try {
      if (form.provinceCode) {
        await fetchWardsByProvince(form.provinceCode);
      }
    } catch (wardError) {
      console.warn('Failed to load wards while editing listing:', wardError);
    }

    locationSearchText.value = composeAddressQuery({ includeDetail: true });

    // Set map marker if lat/lng exists
    if (form.lat && form.lng) {
      setTimeout(() => {
        try {
          setMarkerPosition(form.lat, form.lng, 15);
        } catch (mapError) {
          console.warn('Failed to set edit map marker:', mapError);
        }
      }, 500);
    }

    await nextTick();
    captureInitialEditSnapshot();
  } catch (err) {
    console.error('Failed to load listing for edit:', err);
    setSubmitError('Không thể tải dữ liệu tin đăng để chỉnh sửa.');
  } finally {
    isHydratingEdit.value = false;
    editLoading.value = false;
  }
}

function initializeMap() {
  if (!mapElement.value || map) return;

  map = new maplibregl.Map({
    container: mapElement.value,
    style: buildPostingMapStyle(),
    center: [106.7009, 10.7769],
    zoom: 11,
    minZoom: 5,
    maxZoom: 20,
    pitch: TOP_DOWN_MAP_PITCH,
    maxPitch: 55,
    antialias: true,
    cooperativeGestures: false,
  });

  map.addControl(
    new maplibregl.NavigationControl({
      visualizePitch: true,
    }),
    "top-right"
  );

  map.scrollZoom.enable();
  map.doubleClickZoom.enable();
  map.touchZoomRotate.enable();

  map.on("click", async (event) => {
    const { lat, lng } = event.lngLat;
    setMarkerPosition(lat, lng, 16);
    await reverseGeocodeFromLatLng(lat, lng);
  });

  map.on("load", () => {
    setMapMode(mapMode.value);
    bindNavigationPitchToggle();
  });
}

function replaceMapTilerKey(value, key) {
  if (typeof value === "string") {
    return value.replaceAll("{MAPTILER_KEY}", key);
  }

  if (Array.isArray(value)) {
    return value.map((item) => replaceMapTilerKey(item, key));
  }

  if (value && typeof value === "object") {
    return Object.fromEntries(
      Object.entries(value).map(([entryKey, entryValue]) => [
        entryKey,
        replaceMapTilerKey(entryValue, key),
      ]),
    );
  }

  return value;
}

function buildPostingMapStyle() {
  const mapTilerKey = import.meta.env.VITE_MAPTILER_KEY;
  const style = replaceMapTilerKey(realEstateLightStyle, mapTilerKey);

  style.center = [106.7009, 10.7769];
  style.zoom = 11;
  style.pitch = TOP_DOWN_MAP_PITCH;
  style.sources[POSTING_LOCATION_SOURCE_ID].data = {
    type: "FeatureCollection",
    features: [],
  };
  style.sources["nearby-radius"].data = {
    type: "FeatureCollection",
    features: [],
  };
  style.sources[SATELLITE_SOURCE_ID] = {
    type: "raster",
    tiles: [
      `https://api.maptiler.com/maps/hybrid/256/{z}/{x}/{y}@2x.jpg?key=${mapTilerKey}`,
    ],
    tileSize: 256,
    maxzoom: 20,
  };
  style.layers.splice(1, 0, {
    id: SATELLITE_LAYER_ID,
    type: "raster",
    source: SATELLITE_SOURCE_ID,
    layout: {
      visibility: "none",
    },
    paint: {
      "raster-opacity": 0.92,
      "raster-saturation": -0.18,
      "raster-contrast": 0.08,
      "raster-brightness-min": 0.08,
      "raster-brightness-max": 0.96,
      "raster-resampling": "linear",
      "raster-fade-duration": 0,
    },
  });

  return style;
}

function setMapMode(mode) {
  mapMode.value = mode;

  if (!map?.getLayer(SATELLITE_LAYER_ID)) return;

  const isSatellite = mode === "satellite";
  const pitch = isSatellite || !isMap3dEnabled.value ? TOP_DOWN_MAP_PITCH : STANDARD_MAP_PITCH;

  map.setLayoutProperty(
    SATELLITE_LAYER_ID,
    "visibility",
    isSatellite ? "visible" : "none",
  );

  SATELLITE_HIDDEN_BASE_LAYERS.forEach((layerId) => {
    if (!map.getLayer(layerId)) return;
    map.setLayoutProperty(layerId, "visibility", isSatellite ? "none" : "visible");
  });

  map.easeTo({
    pitch,
    duration: 450,
    essential: true,
  });
}

function toggleMapMode() {
  setMapMode(mapMode.value === "satellite" ? "standard" : "satellite");
}

function toggleMap3d() {
  if (!map) return;

  isMap3dEnabled.value = !isMap3dEnabled.value;

  if (isMap3dEnabled.value && mapMode.value === "satellite") {
    setMapMode("standard");
    return;
  }

  const camera = isMap3dEnabled.value
    ? {
        center: map.getCenter(),
        zoom: Math.max(map.getZoom(), 15.5),
        bearing: map.getBearing(),
        pitch: STANDARD_MAP_PITCH,
      }
    : {
        center: map.getCenter(),
        zoom: map.getZoom(),
        bearing: 0,
        pitch: TOP_DOWN_MAP_PITCH,
      };

  if (isMap3dEnabled.value) {
    lastStandardCamera = camera;
  }

  map.easeTo({
    ...camera,
    duration: 500,
    essential: true,
  });
}

function getCurrentCamera() {
  if (!map) return null;

  return {
    center: map.getCenter(),
    zoom: map.getZoom(),
    bearing: map.getBearing(),
    pitch: map.getPitch(),
  };
}

function toggleStandardMapPitch() {
  if (!map || mapMode.value === "satellite") return false;

  const currentPitch = map.getPitch();
  const isTopDown = currentPitch <= 1;

  if (isTopDown) {
    isMap3dEnabled.value = true;

    const camera = lastStandardCamera || {
      center: map.getCenter(),
      zoom: map.getZoom(),
      bearing: map.getBearing(),
      pitch: STANDARD_MAP_PITCH,
    };

    map.easeTo({
      center: camera.center,
      zoom: camera.zoom,
      bearing: camera.bearing,
      pitch: camera.pitch > 1 ? camera.pitch : STANDARD_MAP_PITCH,
      duration: 450,
      essential: true,
    });
  } else {
    isMap3dEnabled.value = false;
    lastStandardCamera = getCurrentCamera();

    map.easeTo({
      pitch: TOP_DOWN_MAP_PITCH,
      bearing: 0,
      duration: 450,
      essential: true,
    });
  }

  return true;
}

function bindNavigationPitchToggle() {
  if (!mapElement.value) return;

  const compassButton = mapElement.value.querySelector(".maplibregl-ctrl-compass");
  if (!compassButton) return;

  compassButton.addEventListener(
    "click",
    (event) => {
      if (!toggleStandardMapPitch()) return;
      event.preventDefault();
      event.stopImmediatePropagation();
    },
    true,
  );
}

function createPostingLocationFeature(lat, lng) {
  return {
    type: "Feature",
    id: POSTING_LOCATION_FEATURE_ID,
    properties: {
      label: "Vị trí đã chọn",
      title: "Vị trí bất động sản",
    },
    geometry: {
      type: "Point",
      coordinates: [lng, lat],
    },
  };
}

function setMarkerPosition(lat, lng, zoom = 16) {
  const numericLat = Number(lat);
  const numericLng = Number(lng);
  if (!Number.isFinite(numericLat) || !Number.isFinite(numericLng)) return;

  form.lat = numericLat.toFixed(7);
  form.lng = numericLng.toFixed(7);

  if (!map) return;

  const updateMarker = () => {
    map.getSource(POSTING_LOCATION_SOURCE_ID)?.setData({
      type: "FeatureCollection",
      features: [createPostingLocationFeature(numericLat, numericLng)],
    });

    map.setFeatureState(
      {
        source: POSTING_LOCATION_SOURCE_ID,
        id: POSTING_LOCATION_FEATURE_ID,
      },
      {
        selected: true,
      },
    );

    map.easeTo({
      center: [numericLng, numericLat],
      zoom,
      pitch: isMap3dEnabled.value ? STANDARD_MAP_PITCH : TOP_DOWN_MAP_PITCH,
      duration: 550,
      essential: true,
    });
  };

  if (map.getSource(POSTING_LOCATION_SOURCE_ID)) {
    updateMarker();
  } else {
    map.once("load", updateMarker);
  }
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
    await reverseGeocodeFromLatLng(lat, lng);
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

function ensurePropertyTypeOption() {
  if (!currentPropertyTypeOptions.value.length) return;

  const hasSelectedType = currentPropertyTypeOptions.value.some(
    (option) => option.value === form.propertyType,
  );

  if (!hasSelectedType) {
    form.propertyType = currentPropertyTypeOptions.value[0]?.value ?? "";
  }
}

function ensureDemandTypeOption() {
  if (!demandTypeOptions.value.length) return;

  const hasSelectedType = demandTypeOptions.value.some(
    (option) => option.value === form.demandType,
  );

  if (!hasSelectedType) {
    form.demandType = demandTypeOptions.value[0]?.value ?? "";
  }
}

function ensurePosterTypeOption() {
  if (!posterTypeOptions.value.length) return;

  const hasSelectedType = posterTypeOptions.value.some(
    (option) => option.value === form.posterType,
  );

  if (!hasSelectedType) {
    form.posterType = posterTypeOptions.value[0]?.value ?? "";
  }
}

function ensurePostingOptionSelections() {
  ensureDemandTypeOption();
  ensurePropertyTypeOption();
  ensurePosterTypeOption();
}

async function fetchPostingOptions() {
  try {
    const response = await listingService.getPostingOptions();
    postingOptions.value = {
      ...createEmptyPostingOptions(),
      ...(response?.data?.data || {}),
    };
    ensurePostingOptionSelections();
  } catch (error) {
    console.error('Failed to load posting options:', error);
    pushToast('Không thể tải cấu hình đăng tin từ máy chủ.', 'error', 3500);
  }
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
  const remaining = MAX_LISTING_IMAGES - form.images.length;
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

  if (file.size > MAX_VIDEO_SIZE_BYTES) {
    videoUploadError.value = `Dung lượng video vượt quá ${MAX_VIDEO_SIZE_LABEL}. Vui lòng chọn video nhỏ hơn.`;
    pushToast(`Video vượt quá ${MAX_VIDEO_SIZE_LABEL}, vui lòng chọn file nhỏ hơn`, "error", 3500);
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
  return listingMediaUpload.uploadSingle(file, 'image');
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

function removeFrontCard() {
  if (frontCardPreviewUrl.value && String(frontCardPreviewUrl.value).startsWith('blob:')) {
    URL.revokeObjectURL(frontCardPreviewUrl.value);
  }
  frontCardPreviewUrl.value = '';
  form.identityCardFront = null;
  verificationUploadError.value = '';
  pushToast('Đã xóa ảnh CCCD mặt trước', 'success');
}

function removeBackCard() {
  if (backCardPreviewUrl.value && String(backCardPreviewUrl.value).startsWith('blob:')) {
    URL.revokeObjectURL(backCardPreviewUrl.value);
  }
  backCardPreviewUrl.value = '';
  form.identityCardBack = null;
  verificationUploadError.value = '';
  pushToast('Đã xóa ảnh CCCD mặt sau', 'success');
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
  form.province = selectedProvinceName.value;
  form.ward = selectedWardName.value;
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
    if (!value) return `${priceLabel.value} phải lớn hơn 999`;
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) return 'Giá không hợp lệ';
    if (parsed < 1000) return `${priceLabel.value} phải lớn hơn 999`;
    return '';
  }

  if (field === 'rentMinTerm') {
    return '';
  }

  if (field === 'rentPaymentInterval') {
    return '';
  }

  if (field === 'area') {
    if (!value) return 'Diện tích không hợp lệ hoặc vượt giới hạn';
    const parsed = Number(value);
    if (!Number.isFinite(parsed) || parsed <= 0 || parsed > 1000000) return 'Diện tích không hợp lệ hoặc vượt giới hạn';
    return '';
  }

  if (field === 'floorNumber' || field === 'floors') {
    if (!value) return '';
    const parsed = Number(value);
    const label = field === 'floorNumber' ? 'Tầng thứ' : 'Số tầng';
    if (!Number.isInteger(parsed) || parsed < 0 || parsed > 99) return `${label} không được vượt quá 99`;
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

function handlePriceBlur() {
  touchField('price');
  window.setTimeout(() => {
    priceFocused.value = false;
  }, 120);
}

function handlePriceInput(event) {
  onNumberInput(event, 'price', false);
  if (hasPriceValue.value) {
    form.isNegotiable = false;
  }
}

function handleNegotiableChange() {
  if (form.isNegotiable) {
    clearPriceForNegotiable();
  }
}

function clearPriceForNegotiable() {
  form.price = "";
  delete touchedFields.price;
}

function selectPriceSuggestion(value) {
  form.price = String(value);
  form.isNegotiable = false;
  touchField('price');
  priceFocused.value = false;
}

function onPhoneInput(event) {
  const digits = event.target.value.replace(/[^0-9]/g, '');
  // Giới hạn ở 10 chữ số
  const limited = digits.slice(0, 10);
  form.contactPhone = limited;
  event.target.value = limited;
}

function openPreview() {
  if (formBusy.value) return;

  normalizeFormTextFields();
  showPreviewModal.value = true;
}

function openBackConfirm() {
  if (formBusy.value) return;

  if (isEditMode.value) {
    router.back();
    return;
  }

  showDraftConfirm.value = true;
}

function discardAndGoBack() {
  if (formBusy.value) return;

  showDraftConfirm.value = false;
  router.back();
}

function setAppointmentRows(rows) {
  if (!appointmentForm.value) {
    pendingAppointmentRows.value = rows;
    return;
  }

  const exposedRows = appointmentForm.value.appointmentRows;
  if (exposedRows && typeof exposedRows === 'object' && 'value' in exposedRows) {
    exposedRows.value = rows;
  } else {
    appointmentForm.value.appointmentRows = rows;
  }
}

async function saveDraftAndGoBack() {
  if (loading.value) return;

  if (isEditMode.value) {
    router.back();
    return;
  }

  if (savingDraft.value) return;
  savingDraft.value = true;
  clearSubmitError();
  validationErrors.value = {};

  try {
    normalizeFormTextFields();
    touchAllRequired();
    const requiredErrors = getRequiredFieldErrors();
    if (requiredErrors.length) {
      showMissingRequiredToast(requiredErrors);
      showDraftConfirm.value = false;
      return;
    }

    if (form.video && typeof form.video !== 'string' && form.video.size > MAX_VIDEO_SIZE_BYTES) {
      videoUploadError.value = `Dung lượng video vượt quá ${MAX_VIDEO_SIZE_LABEL}. Vui lòng chọn video nhỏ hơn.`;
      pushToast(`Video vượt quá ${MAX_VIDEO_SIZE_LABEL}, vui lòng chọn file nhỏ hơn`, 'error', 3500);
      showDraftConfirm.value = false;
      return;
    }

    const payload = {
      ...form,
      amenities: [...selectedAmenities.value],
      publicInfoAgreed: publicInfoAgreed.value,
      requestVerification: shouldRequestVerification.value,
      saveAsDraft: true,
    };

    if (appointmentForm.value) {
      payload.appointment_slots = appointmentForm.value.getFormData();
    }

    await listingMediaUpload.uploadDraftMediaPayload(payload);
    const response = isEditMode.value
      ? await listingService.update(editListingId.value, payload)
      : await listingService.create(payload);

    const listingId = isEditMode.value ? editListingId.value : response.data?.data?.id;
    if (listingId && Array.isArray(payload.appointment_slots) && payload.appointment_slots.length > 0) {
      await listingService.replaceAppointmentSlots(listingId, payload.appointment_slots);
    }

    clearDraft();
    pushToast(response.data?.message || 'Đã lưu tin nháp', 'success');
    showDraftConfirm.value = false;
    router.push('/profile?tab=listings&status=DRAFT');
  } catch (error) {
    const data = error?.response?.data;
    validationErrors.value = data?.errors || {};
    setSubmitError(data?.message || error?.message || 'Không thể lưu tin nháp. Vui lòng thử lại');
    pushToast(submitError.value, 'error');
  } finally {
    savingDraft.value = false;
  }
}

async function useAccountContactInfo() {
  if (!authStore.user && authStore.token) {
    try {
      await authStore.fetchUser();
    } catch {
      // The toast below covers the unavailable account state.
    }
  }

  const user = authStore.user;
  if (!user) {
    pushToast('Bạn cần đăng nhập để dùng thông tin tài khoản', 'warning');
    return;
  }

  const fullName = user.full_name || user.name || '';
  const phone = String(user.phone || user.phone_number || user.contact_phone || '').replace(/[^0-9]/g, '').slice(0, 10);
  const email = user.email || '';

  if (fullName) form.contactName = fullName;
  if (phone) form.contactPhone = phone;
  if (email) form.contactEmail = String(email).trim().toLowerCase();

  ['contactName', 'contactPhone', 'contactEmail'].forEach((field) => touchField(field));
  pushToast('Đã điền thông tin tài khoản vào phần liên hệ', 'success');
}

const requiredFieldLabels = {
  images: 'Hình ảnh',
  title: 'Tiêu đề',
  description: 'Mô tả',
  propertyType: 'Loại nhà đất',
  area: 'Diện tích',
  price: 'Giá',
  provinceCode: 'Tỉnh/thành phố',
  wardCode: 'Xã/phường',
  streetCode: 'Đường/phố',
  addressDetail: 'Địa chỉ cụ thể',
  contactName: 'Tên liên hệ',
  contactPhone: 'Số điện thoại liên hệ',
  contactEmail: 'Email liên hệ',
};

const requiredFields = Object.keys(requiredFieldLabels);

function touchAllRequired() {
  requiredFields.forEach((field) => touchField(field));
}

function getRequiredFieldErrors() {
  return requiredFields
    .filter((field) => {
      if (field === 'price' && form.isNegotiable) return false;
      touchedFields[field] = true;
      return fieldError(field);
    })
    .map((field) => ({
      field,
      label: requiredFieldLabels[field],
      message: fieldErrorMessage(field),
    }));
}

function showMissingRequiredToast(errors) {
  const missingLabels = errors.map((error) => error.label);
  const visibleLabels = missingLabels.slice(0, 5);
  const moreCount = missingLabels.length - visibleLabels.length;
  const suffix = moreCount > 0 ? ` và ${moreCount} trường khác` : '';
  const message = `Vui lòng bổ sung: ${visibleLabels.join(', ')}${suffix}.`;

  setSubmitError(message);
  validationErrors.value = {};
  pushToast(message, 'error', 5500);
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

function clearVerificationData() {
  clearIdCardPreviews();
  clearLegalDocumentPreviews();
  form.identityCardFront = null;
  form.identityCardBack = null;
  form.legalDocuments = [];
  form.requestVerification = false;
  publicInfoAgreed.value = false;
  verificationUploadError.value = "";
}

function clearMapMarker() {
  form.lat = "";
  form.lng = "";

  if (!map?.getSource(POSTING_LOCATION_SOURCE_ID)) return;

  map.getSource(POSTING_LOCATION_SOURCE_ID).setData({
    type: "FeatureCollection",
    features: [],
  });
}

function resetFormState() {
  initialEditSnapshot.value = "";
  editListingStatus.value = "";
  clearImagePreviews();
  clearIdCardPreviews();
  clearLegalDocumentPreviews();
  Object.assign(form, createInitialState());
  Object.keys(touchedFields).forEach((key) => {
    delete touchedFields[key];
  });
  showLegalDropdown.value = false;
  showMoreDetail.value = true;
  showVerificationSection.value = true;
  publicInfoAgreed.value = false;
  selectedAmenities.value = [];
  pendingAppointmentRows.value = null;
  existingAppointmentSlotIds.value = [];
  appointmentForm.value?.resetForm?.();
  locationSearchText.value = "";
  locationLoadError.value = "";
  clearSubmitError();
  validationErrors.value = {};
  imageUploadError.value = "";
  videoUploadError.value = "";
  verificationUploadError.value = "";
  videoPreviewName.value = "";
  priceFocused.value = false;
  activeScoreSection.value = 'media';
  clearMapMarker();
}

function resetForm() {
  resetFormState();
}

onBeforeUnmount(() => {
  clearSubmitError();
  if (map) {
    map.remove();
    map = null;
  }
  clearImagePreviews();
  clearIdCardPreviews();
});

function validateCompleteVerificationDocuments(requireVerification) {
  if (form.demandType === 'RENT') return true;
  if (!requireVerification && !hasAnyVerificationDocuments.value) return true;
  if (shouldRequestVerification.value) {
    verificationUploadError.value = "";
    return true;
  }

  const missing = [];
  if (!form.identityCardFront) missing.push('CCCD/CMND mặt trước');
  if (!form.identityCardBack) missing.push('CCCD/CMND mặt sau');
  if (!Array.isArray(form.legalDocuments) || form.legalDocuments.length === 0) {
    missing.push('ít nhất một ảnh giấy tờ pháp lý');
  }

  const message = `Vui lòng tải lên ${missing.join(', ')} để gửi yêu cầu xác thực.`;
  verificationUploadError.value = message;
  setSubmitError(message);
  pushToast(message, 'error', 5000);
  showVerificationSection.value = true;

  return false;
}

async function submitVerificationOnly() {
  if (loading.value) {
    pushToast('Bạn đã nhấn lưu quá nhanh', 'warning');
    return;
  }

  if (!validateCompleteVerificationDocuments(true)) {
    return;
  }

  loading.value = true;
  clearSubmitError();
  validationErrors.value = {};

  try {
    const payload = await listingMediaUpload.uploadVerificationPayload({
      identityCardFront: form.identityCardFront,
      identityCardBack: form.identityCardBack,
      legalDocuments: form.legalDocuments || [],
      publicInfoAgreed: publicInfoAgreed.value,
    });

    const response = await listingService.updateVerification(editListingId.value, payload);
    pushToast(response.data?.message || 'Cập nhật thông tin xác thực thành công', 'success');
    router.push('/profile?tab=listings');
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      validationErrors.value = data?.errors || {};
      setSubmitError(data?.message || 'Không thể lưu thông tin xác thực. Vui lòng thử lại');
    } else {
      setSubmitError(error.message || 'Không thể lưu thông tin xác thực. Vui lòng thử lại');
    }

    pushToast(submitError.value, 'error');
  } finally {
    loading.value = false;
  }
}

async function submitListing() {
  if (loading.value) {
    pushToast('Bạn đã nhấn đăng tin quá nhanh', 'warning');
    return;
  }

  if (isEditMode.value && !isUnlistedRelistingMode.value && !isEditDirty.value) {
    return;
  }

  // Validate required fields first
  touchAllRequired();
  const requiredErrors = getRequiredFieldErrors();
  if (requiredErrors.length) {
    showMissingRequiredToast(requiredErrors);
    return;
  }

  if (!validateCompleteVerificationDocuments(false)) {
    return;
  }

  if (isBelowMinimumScore.value) {
    setSubmitError(`Tin đăng đang dưới điểm tối thiểu ${minimumScore.value}đ`);
    pushToast(`Tin đăng dưới điểm tối thiểu ${minimumScore.value}đ. Vui lòng bổ sung thông tin trước khi đăng.`, 'error', 3500);
    return;
  }

  loading.value = true;
  clearSubmitError();
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
        setSubmitError('Lịch hẹn xem nhà không hợp lệ');
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
    pushToast('Đang tải ảnh và giấy tờ lên...', 'info', 2500);
    await listingMediaUpload.uploadListingMediaPayload(form);

    // 4. Submit to Backend
    pushToast(isRelistingMode.value ? 'Đang đăng lại tin...' : (isEditMode.value ? 'Đang cập nhật tin đăng...' : 'Đang gửi tin đăng...'), 'info', 2500);
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
      if (listingId !== undefined && listingId !== null && slotsPayload.length > 0) {
        await listingService.replaceAppointmentSlots(listingId, slotsPayload);
      }
    } catch (slotErr) {
      console.error('Failed to save appointment slots:', slotErr);
      // don't block overall success, but notify user
      pushToast('Lưu khung giờ xem nhà thất bại (không ảnh hưởng tới tin đăng)', 'warning');
    }

    clearSubmitError();
    clearDraft();
    const successMessage = isRelistingMode.value
      ? 'Đăng lại tin thành công. Tin đăng đang chờ duyệt.'
      : (response.data?.message || (isEditMode.value ? 'Cập nhật tin thành công' : 'Đăng tin thành công. Tin đăng chờ duyệt'));
    pushToast(successMessage, 'success');
    resetForm();
    // Redirect đến trang danh sách tin đăng
    router.push('/profile?tab=listings');
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      const statusCode = error.response.status;
      validationErrors.value = data?.errors || {};

      if (statusCode === 401) {
        setSubmitError('Phiên làm việc đã hết hạn');
      } else if (statusCode === 500) {
        setSubmitError('Đã xảy ra lỗi hệ thống');
      } else if (statusCode >= 400 && statusCode < 500) {
        setSubmitError(data?.message || 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại');
      } else {
        setSubmitError(data?.message || 'Không thể lưu tin đăng. Vui lòng thử lại');
      }

      pushToast(submitError.value, 'error');
    } else {
      setSubmitError(error.code === 'ECONNABORTED'
        ? 'Kết nối quá lâu, vui lòng kiểm tra mạng và thử lại.'
        : (error.message || 'Upload thất bại. Vui lòng thử lại'));
      pushToast(submitError.value, 'error');
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

.form-interaction-locked {
  pointer-events: none;
  user-select: none;
}

.preview-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.45);
  padding: 24px;
}

.preview-modal {
  width: min(1400px, calc(100vw - 48px));
  max-height: calc(100vh - 48px);
  overflow: hidden;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.28);
}

.preview-modal-detail {
  display: flex;
  flex-direction: column;
}

.preview-header,
.preview-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 18px;
}

.preview-header {
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 2;
  background: #fff;
}

.preview-footer {
  border-top: 1px solid #e2e8f0;
}

.preview-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.preview-content {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 28px;
  max-height: calc(100vh - 150px);
  overflow-y: auto;
  padding: 22px;
}

.preview-summary {
  align-self: start;
  position: sticky;
  top: 0;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #fff;
  padding: 18px;
}

.preview-detail-body {
  max-height: calc(100vh - 132px);
  overflow-y: auto;
  background: #f4f8fc;
  padding: 18px 22px 24px;
}

.preview-static-frame {
  pointer-events: none;
  user-select: none;
}

.draft-confirm-modal {
  width: min(440px, 100%);
  border-radius: 18px;
  background: #fff;
  padding: 22px;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.28);
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

.contact-action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
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

.price-field {
  position: relative;
  display: block;
}

.price-error-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  display: inline-flex;
  height: 16px;
  width: 16px;
  transform: translateY(-50%);
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  line-height: 1;
}

.price-suggestion-panel {
  margin-top: 8px;
  overflow: hidden;
  border: 1px solid #38bdf8;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
}

.price-suggestion-item {
  display: block;
  width: 100%;
  padding: 12px 14px;
  text-align: left;
  font-size: 13px;
  color: #0f172a;
  transition: background 0.15s ease, color 0.15s ease;
}

.price-suggestion-item:hover {
  background: #eff6ff;
  color: #0284c7;
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
  height: 360px;
  z-index: 1;
}

.legal-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.legal-field {
  position: relative;
  z-index: 20;
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
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  z-index: 50;
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid #38bdf8;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.16);
}

.legal-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  min-height: 40px;
  padding: 9px 12px;
  font-size: 13px;
  color: #0f172a;
  border-bottom: 1px solid #e6edf5;
  background: #fff;
}

.legal-option.selected {
  background: #2563eb;
  color: #fff;
}

.legal-option:hover {
  background: #eff6ff;
}

.legal-option.selected:hover {
  background: #1d4ed8;
}

.legal-option:last-child {
  border-bottom: none;
}

.legal-option input[type="checkbox"] {
  width: 14px;
  height: 14px;
  accent-color: #2563eb;
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
  position: relative;
  display: flex;
  min-height: 96px;
  align-items: center;
  justify-content: center;
  border: 1px dashed #cdd8e6;
  border-radius: 12px;
  background: #f8fbff;
}

.file-box-inner {
  position: relative;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.verification-image-remove {
  position: absolute;
  right: -12px;
  top: -8px;
  z-index: 2;
  display: flex;
  width: 22px;
  height: 22px;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
  font-size: 16px;
  line-height: 1;
  opacity: 0;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
  transition: opacity 0.15s ease, background-color 0.15s ease;
}

.file-box:hover .verification-image-remove,
.verification-image-remove:focus-visible {
  opacity: 1;
}

.verification-image-remove:hover {
  background: #dc2626;
}

@media (hover: none) {
  .verification-image-remove {
    opacity: 1;
  }
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
  border-radius: 10px;
  padding: 4px 6px;
  margin: 0 -6px;
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

.score-item-row {
  border-radius: 8px;
  padding: 2px 6px;
  margin-left: -6px;
  margin-right: -6px;
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
