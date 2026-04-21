<template>
  <main class="min-h-screen bg-[#f4f8fc] pb-14 pt-32 lg:pt-40">
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
          <p v-if="submitAttempted && requiredImageError" class="field-error mt-2">{{ requiredImageError }}</p>
          <ul class="mt-2 list-disc pl-4 text-[11px] text-slate-500">
            <li>Hỗ trợ jpg, jpeg, png. Tối đa 10 ảnh.</li>
            <li>Kích thước mỗi ảnh tối đa 30MB, video tối đa 100MB.</li>
          </ul>

          <div v-if="imagePreviews.length" class="mt-3">
            <p class="text-xs font-semibold text-slate-700">Ảnh đã chọn ({{ imagePreviews.length }})</p>
            <div class="preview-grid mt-2">
              <figure v-for="(preview, idx) in imagePreviews" :key="preview.url" class="preview-card group">
              <figure v-for="(preview, idx) in imagePreviews" :key="preview.url" class="preview-card group">
                <img :src="preview.url" :alt="preview.name" class="preview-image" />
                <button
                  type="button"
                  class="absolute right-1 top-1 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-600"
                  title="Xóa ảnh"
                  @click="removeImage(idx)"
                >✕</button>
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
            <input
              v-model="form.title"
              class="input mt-1"
              :class="fieldErrorClass('title')"
              maxlength="120"
              inputmode="text"
              autocomplete="off"
              placeholder="Nhập tiêu đề"
              @blur="touchField('title')"
            />
            <p class="mt-1 text-right text-[12px] text-slate-400">{{ titleCount }}/120 ký tự</p>
            <p v-if="showFieldError('title')" class="field-error">{{ fieldError('title') }}</p>
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
            <span class="field-label">Mô tả *</span>
            <textarea
              v-model="form.description"
              class="input mt-1 min-h-[140px]"
              :class="fieldErrorClass('description')"
              maxlength="5000"
              placeholder="VD: Giới thiệu các đặc điểm nổi bật của bất động sản:&#10;- Các tiện ích xung quanh: gần công viên, gần trường học&#10;- Thời gian đến khu vực trung tâm, tiện ích xung quanh"
              @blur="touchField('description')"
            ></textarea>
            <p class="mt-1 text-right text-[12px] text-slate-400">{{ descriptionCount }}/5000</p>
            <p v-if="showFieldError('description')" class="field-error">{{ fieldError('description') }}</p>
          </label>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Loại nhà đất <span class="text-red-500">*</span></span>
              <select v-model="form.propertyType" class="input mt-1" :class="fieldErrorClass('propertyType')" @blur="touchField('propertyType')">
                <option v-for="option in currentPropertyTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <p v-if="showFieldError('propertyType')" class="field-error">{{ fieldError('propertyType') }}</p>
            </label>
            <label>
              <span class="field-label">Giấy tờ pháp lý</span>
              <button type="button" class="input mt-1 legal-trigger" @click="toggleLegalDropdown">
                <span v-if="!form.legalPaperTypes.length" class="text-slate-400">Chọn giấy tờ pháp lý</span>
                <span v-else class="legal-selected-text">{{ selectedLegalPaperLabels }}</span>
                <span class="text-slate-500">▾</span>
              </button>
              <div v-if="showLegalDropdown" class="legal-dropdown mt-2">
                <label v-for="option in legalPaperOptions" :key="option.value" class="legal-option">
                  <span>{{ option.label }}</span>
                  <input type="checkbox" :checked="form.legalPaperTypes.includes(option.value)" @change="toggleLegalPaper(option.value)" />
                </label>
              </div>
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-1">
            <label>
              <span class="field-label">Diện tích (m2) <span class="text-red-500">*</span></span>
              <input
                v-model="form.area"
                class="input mt-1"
                :class="fieldErrorClass('area')"
                type="number"
                min="0"
                step="0.1"
                inputmode="decimal"
                @keydown="blockNegativeNumberKey"
                @input="sanitizeNonNegativeNumber('area', $event, true)"
                @blur="touchField('area')"
                placeholder="Nhập số"
              />
              <p v-if="showFieldError('area')" class="field-error">{{ fieldError('area') }}</p>
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-1">
            <label>
              <span class="field-label">{{ priceLabel.replace('*', '').trim() }} <span class="text-red-500">*</span></span>
              <input
                v-model="form.price"
                class="input mt-1 disabled:bg-slate-100"
                :class="fieldErrorClass('price')"
                :disabled="form.isNegotiable"
                type="number"
                min="0"
                step="0.1"
                inputmode="decimal"
                @keydown="blockNegativeNumberKey"
                @input="sanitizeNonNegativeNumber('price', $event, true)"
                @blur="touchField('price')"
                placeholder="Nhập số"
              />
              <p v-if="showFieldError('price')" class="field-error">{{ fieldError('price') }}</p>
            </label>
          </div>

          <div class="mt-3 flex items-center gap-2">
            <input id="is-negotiable-info" v-model="form.isNegotiable" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
            <label for="is-negotiable-info" class="text-[14px] text-slate-500">Giá Thương lượng</label>
          </div>
        </section>

        <section class="section-card">
          <header class="section-title">
            <img :src="locationImageIcon" alt="location" class="h-5 w-5" />
            <h2>Vị trí</h2>
          </header>

          <label class="mt-3 block">
            <span class="field-label">Tìm kiếm địa chỉ bất động sản <span class="text-red-500">*</span></span>
            <div class="mt-1 flex gap-2">
              <input
                v-model="locationSearchText"
                class="input"
                inputmode="text"
                autocomplete="off"
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
              <span class="field-label">Tỉnh / Thành phố <span class="text-red-500">*</span></span>
              <select v-model="form.provinceCode" class="input mt-1" :class="fieldErrorClass('provinceCode')" @blur="touchField('provinceCode')">
                <option value="">Chọn Tỉnh/Thành phố</option>
                <option v-for="province in provinces" :key="province.code" :value="String(province.code)">
                  {{ province.name }}
                </option>
              </select>
              <p v-if="showFieldError('provinceCode')" class="field-error">{{ fieldError('provinceCode') }}</p>
            </label>
            <label>
              <span class="field-label">Quận / Huyện <span class="text-red-500">*</span></span>
              <select v-model="form.districtCode" class="input mt-1" :class="fieldErrorClass('districtCode')" :disabled="!form.provinceCode || districtsLoading" @blur="touchField('districtCode')">
                <option value="">{{ districtsLoading ? 'Đang tải quận/huyện...' : 'Chọn Quận/Huyện' }}</option>
                <option v-for="district in districts" :key="district.code" :value="String(district.code)">
                  {{ district.name }}
                </option>
              </select>
              <p v-if="showFieldError('districtCode')" class="field-error">{{ fieldError('districtCode') }}</p>
            </label>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label>
              <span class="field-label">Phường / Xã <span class="text-red-500">*</span></span>
              <select v-model="form.wardCode" class="input mt-1" :class="fieldErrorClass('wardCode')" :disabled="!form.districtCode || wardsLoading" @blur="touchField('wardCode')">
                <option value="">{{ wardsLoading ? 'Đang tải phường/xã...' : 'Chọn Phường/Xã' }}</option>
                <option v-for="ward in wards" :key="ward.code" :value="String(ward.code)">
                  {{ ward.name }}
                </option>
              </select>
              <p v-if="showFieldError('wardCode')" class="field-error">{{ fieldError('wardCode') }}</p>
            </label>
            <label>
              <span class="field-label">Đường / Phố</span>
              <input v-model="form.streetCode" class="input mt-1" inputmode="text" autocomplete="off" placeholder="Chọn Đường/Phố" @blur="touchField('streetCode')" />
            </label>
          </div>

          <label class="mt-3 block">
            <span class="field-label">Địa chỉ cụ thể</span>
            <input v-model="form.addressDetail" class="input mt-1" inputmode="text" autocomplete="off" placeholder="Nhập địa chỉ" @blur="touchField('addressDetail')" />
          </label>

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
                <span>Số phòng ngủ <span class="text-red-500">*</span></span>
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
                <input
                  v-model="form.bedrooms"
                  class="quick-input"
                  :class="fieldErrorClass('bedrooms')"
                  type="number"
                  min="0"
                  inputmode="numeric"
                  @keydown="blockNegativeNumberKey"
                  @input="sanitizeNonNegativeNumber('bedrooms', $event, false)"
                  @blur="touchField('bedrooms')"
                  placeholder="Nhập số"
                />
              </div>
            </div>

            <div>
              <p class="field-label with-icon">
                <img :src="bathIcon" alt="bath" class="h-4 w-4" />
                <span>Số phòng tắm <span class="text-red-500">*</span></span>
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
                <input
                  v-model="form.bathrooms"
                  class="quick-input"
                  :class="fieldErrorClass('bathrooms')"
                  type="number"
                  min="0"
                  inputmode="numeric"
                  @keydown="blockNegativeNumberKey"
                  @input="sanitizeNonNegativeNumber('bathrooms', $event, false)"
                  @blur="touchField('bathrooms')"
                  placeholder="Nhập số"
                />
              </div>
            </div>
          </div>

          <div class="mt-3 grid gap-4 md:grid-cols-2">
            <label>
              <span class="field-label">Mặt tiền <span class="text-red-500">*</span></span>
              <div class="unit-input mt-2">
                <input
                  v-model="form.facadeWidth"
                  class="input"
                  :class="fieldErrorClass('facadeWidth')"
                  type="number"
                  min="0"
                  step="0.1"
                  inputmode="decimal"
                  @keydown="blockNegativeNumberKey"
                  @input="sanitizeNonNegativeNumber('facadeWidth', $event, true)"
                  @blur="touchField('facadeWidth')"
                  placeholder="Nhập số"
                />
                <span class="unit-label">m</span>
              </div>
            </label>
            <label>
              <span class="field-label">Chiều sâu <span class="text-red-500">*</span></span>
              <div class="unit-input mt-2">
                <input
                  v-model="form.depth"
                  class="input"
                  :class="fieldErrorClass('depth')"
                  type="number"
                  min="0"
                  step="0.1"
                  inputmode="decimal"
                  @keydown="blockNegativeNumberKey"
                  @input="sanitizeNonNegativeNumber('depth', $event, true)"
                  @blur="touchField('depth')"
                  placeholder="Nhập số"
                />
                <span class="unit-label">m</span>
              </div>
            </label>
          </div>

          <div class="mt-4">
            <button type="button" class="more-info-toggle" @click="showMoreDetail = !showMoreDetail">
              <span class="field-label">Thêm thông tin</span>
              <span class="more-info-badge">Điền đủ thông tin, tăng lượt tiếp cận</span>
              <span class="more-info-arrow" :class="{ open: showMoreDetail }">⌄</span>
            </button>
          </div>

          <div v-if="showMoreDetail" class="mt-3 space-y-3">
            <div class="grid gap-4 md:grid-cols-2">
              <label>
                <span class="field-label">Tầng thứ <span class="text-red-500">*</span></span>
                <div class="unit-input mt-2">
                  <input
                    v-model="form.floorNumber"
                    class="input"
                    :class="fieldErrorClass('floorNumber')"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    @keydown="blockNegativeNumberKey"
                    @input="sanitizeNonNegativeNumber('floorNumber', $event, false)"
                    @blur="touchField('floorNumber')"
                    placeholder="Nhập số"
                  />
                  <span class="unit-label">m</span>
                </div>
              </label>
              <label>
                <span class="field-label">Số tầng <span class="text-red-500">*</span></span>
                <div class="unit-input mt-2">
                  <input
                    v-model="form.floors"
                    class="input"
                    :class="fieldErrorClass('floors')"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    @keydown="blockNegativeNumberKey"
                    @input="sanitizeNonNegativeNumber('floors', $event, false)"
                    @blur="touchField('floors')"
                    placeholder="Nhập số"
                  />
                  <span class="unit-label">m</span>
                </div>
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
              <p class="field-label">Số ban công <span class="text-red-500">*</span></p>
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
                <input
                  v-model="form.balconies"
                  class="quick-input"
                  :class="fieldErrorClass('balconies')"
                  type="number"
                  min="0"
                  inputmode="numeric"
                  @keydown="blockNegativeNumberKey"
                  @input="sanitizeNonNegativeNumber('balconies', $event, false)"
                  @blur="touchField('balconies')"
                  placeholder="Nhập số"
                />
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
              <span class="field-label">Họ và tên <span class="text-red-500">*</span></span>
              <input v-model="form.contactName" class="input mt-1" :class="fieldErrorClass('contactName')" inputmode="text" autocomplete="name" placeholder="Nhập họ tên" @blur="touchField('contactName')" />
              <p v-if="showFieldError('contactName')" class="field-error">{{ fieldError('contactName') }}</p>
            </label>
            <label>
              <span class="field-label">Số điện thoại <span class="text-red-500">*</span></span>
              <input
                v-model="form.contactPhone"
                class="input mt-1"
                :class="fieldErrorClass('contactPhone')"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="tel"
                placeholder="VD: 0912345678"
                @keydown="blockPhoneKey"
                @input="sanitizePhoneNumber"
                @blur="touchField('contactPhone')"
              />
              <p v-if="showFieldError('contactPhone')" class="field-error">{{ fieldError('contactPhone') }}</p>
            </label>
          </div>

          <label class="mt-3 block">
            <span class="field-label">Email</span>
            <input
              v-model="form.contactEmail"
              class="input mt-1"
              :class="fieldErrorClass('contactEmail')"
              type="email"
              inputmode="email"
              autocomplete="email"
              placeholder="vd_email@example.com"
              @input="touchField('contactEmail')"
              @blur="touchField('contactEmail')"
            />
            <p v-if="showFieldError('contactEmail')" class="field-error">{{ fieldError('contactEmail') }}</p>
          </label>
        </section>

        <section class="section-card">
          <button type="button" class="appointment-header" @click="showAppointmentSection = !showAppointmentSection">
            <span class="inline-flex items-center gap-2">
              <img :src="homeImageIcon" alt="schedule" class="h-5 w-5" />
              <h2>Đặt lịch xem nhà</h2>
              <img :src="infoDotIcon" alt="info" class="h-4 w-4 opacity-70" />
            </span>
            <span class="chevron" :class="{ open: showAppointmentSection }">⌃</span>
          </button>

          <div v-if="showAppointmentSection" class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="relative">
              <span class="field-label">* Chọn ngày</span>
              <button type="button" class="input mt-2 legal-trigger" @click="showDayDropdown = !showDayDropdown">
                <span class="legal-selected-text">{{ selectedAppointmentDayLabel }}</span>
                <span class="text-slate-500">⌄</span>
              </button>
              <div v-if="showDayDropdown" class="legal-dropdown mt-2">
                <label class="legal-option">
                  <span>Chọn tất cả các ngày</span>
                  <input type="checkbox" :checked="isAllDaysSelected" @change="toggleAllAppointmentDays" />
                </label>
                <label v-for="day in appointmentDayOptions" :key="day.value" class="legal-option">
                  <span>{{ day.label }}</span>
                  <input
                    type="checkbox"
                    :checked="selectedAppointmentDays.includes(day.value)"
                    @change="toggleAppointmentDay(day.value)"
                  />
                </label>
              </div>
            </div>

            <div class="relative">
              <span class="field-label">* Chọn giờ</span>
              <button type="button" class="input mt-2 legal-trigger" @click="showTimeDropdown = !showTimeDropdown">
                <span class="legal-selected-text">{{ selectedAppointmentTimeLabel }}</span>
                <span class="text-slate-500">⌄</span>
              </button>
              <div v-if="showTimeDropdown" class="legal-dropdown mt-2">
                <button
                  v-for="slot in appointmentTimeOptions"
                  :key="slot.value"
                  type="button"
                  class="legal-option w-full"
                  @click="selectAppointmentTime(slot.value)"
                >
                  <span>{{ slot.label }}</span>
                  <span v-if="appointmentTimeSlot === slot.value">✓</span>
                </button>
              </div>
            </div>
          </div>
        </section>

        <section class="section-card">
          <button type="button" class="appointment-header" @click="showVerificationSection = !showVerificationSection">
            <span class="inline-flex items-center gap-2">
              <img :src="homeImageIcon" alt="verify" class="h-5 w-5" />
              <h2>Xác thực bất động sản</h2>
            </span>
            <span class="chevron" :class="{ open: showVerificationSection }">⌃</span>
          </button>

          <div v-if="showVerificationSection" class="mt-4 space-y-4">
            <div class="verify-note-box">
              <p class="text-lg tracking-wide">⭐ ⭐ ⭐</p>
              <p class="mt-1 text-[20px] font-semibold text-amber-900">Xác thực bất động sản tại Meey Land</p>
              <p class="mt-1 text-sm text-slate-600">Khi hoàn thành, tin đăng sẽ được ưu tiên vị trí hiển thị trên Meey Land</p>
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
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import * as L from "leaflet";
import "leaflet/dist/leaflet.css";
import listingService from "@/services/listingService";
import cloudinaryService from "@/services/cloudinaryService";
import cloudinaryService from "@/services/cloudinaryService";
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

const appointmentDayOptions = [
  { value: 1, label: "Thứ 2" },
  { value: 2, label: "Thứ 3" },
  { value: 3, label: "Thứ 4" },
  { value: 4, label: "Thứ 5" },
  { value: 5, label: "Thứ 6" },
  { value: 6, label: "Thứ 7" },
  { value: 0, label: "Chủ nhật" },
];

const appointmentTimeOptions = [
  { value: "ALL_DAY", label: "Tất cả các giờ (8h-23h)", startHour: 8, startMinute: 0 },
  { value: "OFFICE", label: "Giờ hành chính (8h - 18h)", startHour: 8, startMinute: 0 },
  { value: "AFTER_HOURS", label: "Ngoài giờ hành chính (18h30 - 23h)", startHour: 18, startMinute: 30 },
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
    appointmentAt: "",
    appointmentDays: [],
    appointmentTimeSlot: "",
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
const submitAttempted = ref(false);
const touchedFields = ref({});
const locationSearchText = ref("");
const mapElement = ref(null);
let map = null;
let locationMarker = null;
const provinces = ref([]);
const districts = ref([]);
const wards = ref([]);
const districtsLoading = ref(false);
const wardsLoading = ref(false);
const locationSearching = ref(false);
const locationLoadError = ref("");
const imagePreviews = ref([]);
const frontCardPreviewUrl = ref("");
const backCardPreviewUrl = ref("");
const showLegalDropdown = ref(false);
const showMoreDetail = ref(true);
const selectedAmenities = ref([]);
const showAppointmentSection = ref(true);
const showVerificationSection = ref(true);
const publicInfoAgreed = ref(false);
const showDayDropdown = ref(false);
const showTimeDropdown = ref(false);
const selectedAppointmentDays = ref([]);
const appointmentTimeSlot = ref("");

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

const selectedLegalPaperLabels = computed(() => {
  return legalPaperOptions
    .filter((option) => form.legalPaperTypes.includes(option.value))
    .map((option) => option.label)
    .join(", ");
});

const isAllDaysSelected = computed(
  () => selectedAppointmentDays.value.length === appointmentDayOptions.length,
);

const selectedAppointmentDayLabel = computed(() => {
  if (!selectedAppointmentDays.value.length) return "Chọn ngày";
  if (isAllDaysSelected.value) return "Chọn tất cả các ngày";

  return appointmentDayOptions
    .filter((day) => selectedAppointmentDays.value.includes(day.value))
    .map((day) => day.label)
    .join(", ");
});

const selectedAppointmentTimeLabel = computed(() => {
  if (!appointmentTimeSlot.value) return "Chọn giờ";
  return appointmentTimeOptions.find((slot) => slot.value === appointmentTimeSlot.value)?.label || "Chọn giờ";
});

const shouldRequestVerification = computed(() => {
  return Boolean(form.identityCardFront || form.identityCardBack || (form.legalDocuments && form.legalDocuments.length));
});

const titleCount = computed(() => form.title.length);
const descriptionCount = computed(() => form.description.length);
const priceLabel = computed(() => (form.demandType === "RENT" ? "Giá thuê *" : "Giá bán *"));

const currentPropertyTypeOptions = computed(() =>
  form.demandType === "RENT" ? rentPropertyTypeOptions : salePropertyTypeOptions,
);

const selectedProvinceName = computed(() => {
  const item = provinces.value.find((province) => String(province.code) === form.provinceCode);
  return item?.name || "";
});

const selectedDistrictName = computed(() => {
  const item = districts.value.find((district) => String(district.code) === form.districtCode);
  return item?.name || "";
});

const selectedWardName = computed(() => {
  const item = wards.value.find((ward) => String(ward.code) === form.wardCode);
  return item?.name || "";
});

const fieldValidators = {
  title: () => (!form.title.trim() ? "Vui lòng nhập tiêu đề." : ""),
  description: () => (!form.description.trim() ? "Vui lòng nhập mô tả." : form.description.trim().length < 20 ? "Mô tả phải có ít nhất 20 ký tự." : ""),
  propertyType: () => (!form.propertyType ? "Vui lòng chọn loại nhà đất." : ""),
  provinceCode: () => (!form.provinceCode ? "Vui lòng chọn tỉnh/thành phố." : ""),
  districtCode: () => (!form.districtCode ? "Vui lòng chọn quận/huyện." : ""),
  wardCode: () => (!form.wardCode ? "Vui lòng chọn phường/xã." : ""),
  area: () => (!form.area || Number(form.area) <= 0 ? "Diện tích phải lớn hơn 0." : ""),
  price: () => (form.isNegotiable ? "" : !form.price || Number(form.price) <= 0 ? "Vui lòng nhập giá lớn hơn 0." : ""),
  bedrooms: () => (form.bedrooms === "" ? "Vui lòng nhập số phòng ngủ." : Number(form.bedrooms) < 0 ? "Không được nhập số âm." : ""),
  bathrooms: () => (form.bathrooms === "" ? "Vui lòng nhập số phòng tắm." : Number(form.bathrooms) < 0 ? "Không được nhập số âm." : ""),
  floors: () => (form.floors === "" ? "Vui lòng nhập số tầng." : Number(form.floors) < 0 ? "Không được nhập số âm." : ""),
  floorNumber: () => (form.floorNumber === "" ? "Vui lòng nhập tầng thứ." : Number(form.floorNumber) < 0 ? "Không được nhập số âm." : ""),
  balconies: () => (form.balconies === "" ? "Vui lòng nhập số ban công." : Number(form.balconies) < 0 ? "Không được nhập số âm." : ""),
  facadeWidth: () => (form.facadeWidth === "" ? "Vui lòng nhập mặt tiền." : Number(form.facadeWidth) < 0 ? "Không được nhập số âm." : ""),
  depth: () => (form.depth === "" ? "Vui lòng nhập chiều sâu." : Number(form.depth) < 0 ? "Không được nhập số âm." : ""),
  contactName: () => (!form.contactName.trim() ? "Vui lòng nhập họ và tên." : ""),
  contactPhone: () => (!form.contactPhone.trim() ? "Vui lòng nhập số điện thoại." : ""),
  contactEmail: () => {
    const email = String(form.contactEmail || "").trim();
    if (!email) return "";
    const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
    return emailRegex.test(email) ? "" : "Email phải có đuôi @gmail.com.";
  },
};

const requiredImageError = computed(() => (form.images.length === 0 ? "Vui lòng tải lên ít nhất 1 ảnh." : ""));

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
    form.districtCode = "";
    form.wardCode = "";
    districts.value = [];
    wards.value = [];

    if (!newValue) return;
    await fetchDistrictsByProvince(newValue);

    await geocodeAddressToMap(composeAddressQuery({ includeDetail: false }), 11);
  },
);

watch(
  () => form.districtCode,
  async (newValue) => {
    form.wardCode = "";
    wards.value = [];

    if (!newValue) return;
    await fetchWardsByDistrict(newValue);

    await geocodeAddressToMap(composeAddressQuery({ includeDetail: false }), 13);
  },
);

watch([selectedAppointmentDays, appointmentTimeSlot], () => {
  form.appointmentAt = buildNextAppointmentDateTime();
});

watch(
  () => form.wardCode,
  async (newValue) => {
    if (!newValue) return;
    await geocodeAddressToMap(composeAddressQuery({ includeDetail: false }), 15);
  },
);

onMounted(async () => {
  initializeMap();
  await fetchProvinces();
});

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

  if (selectedWardName.value) chunks.push(selectedWardName.value);
  if (selectedDistrictName.value) chunks.push(selectedDistrictName.value);
  if (selectedProvinceName.value) chunks.push(selectedProvinceName.value);

  chunks.push("Việt Nam");
  return chunks.filter(Boolean).join(", ");
}

async function geocodeAddressToMap(address, zoom = 15) {
  if (!address || !map) return;

  try {
    locationSearching.value = true;
    const params = new URLSearchParams({
      q: address,
      format: "json",
      addressdetails: "1",
      limit: "1",
      countrycodes: "vn",
      "accept-language": "vi",
    });

    const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`);
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
      lon: String(lng),
      format: "json",
      addressdetails: "1",
      zoom: "18",
      "accept-language": "vi",
    });
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params.toString()}`);
    if (!response.ok) return;

    const data = await response.json();
    if (!data) return;

    locationSearchText.value = data.display_name || "";

    const road = data.address?.road || data.address?.pedestrian || data.address?.residential || "";
    if (road) {
      form.streetCode = road;
    }

    const houseNumber = data.address?.house_number || "";
    form.addressDetail = [houseNumber, road].filter(Boolean).join(" ").trim();

    await syncAdministrativeCodesFromAddress(data.address || {});
  } catch {
    // Ignore reverse geocode failures.
  }
}

function normalizeAdminName(value) {
  return String(value || "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/thanh pho|tinh|quan|huyen|thi xa|thi tran|phuong|xa|tp\.?/g, "")
    .replace(/[^a-z0-9\s]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function findProvinceByAddress(address) {
  const candidates = [address.city, address.state, address.province, address.region].filter(Boolean);
  if (!candidates.length) return null;

  return provinces.value.find((province) => {
    const provinceName = normalizeAdminName(province.name);
    return candidates.some((candidate) => {
      const normalized = normalizeAdminName(candidate);
      return provinceName === normalized || provinceName.includes(normalized) || normalized.includes(provinceName);
    });
  }) || null;
}

function findDistrictByAddress(address) {
  const candidates = [address.state_district, address.county, address.city_district].filter(Boolean);
  if (!candidates.length) return null;

  return districts.value.find((district) => {
    const districtName = normalizeAdminName(district.name);
    return candidates.some((candidate) => {
      const normalized = normalizeAdminName(candidate);
      return districtName === normalized || districtName.includes(normalized) || normalized.includes(districtName);
    });
  }) || null;
}

function findWardByAddress(address) {
  const candidates = [address.suburb, address.quarter, address.ward, address.village, address.town].filter(Boolean);
  if (!candidates.length) return null;

  return wards.value.find((ward) => {
    const wardName = normalizeAdminName(ward.name);
    return candidates.some((candidate) => {
      const normalized = normalizeAdminName(candidate);
      return wardName === normalized || wardName.includes(normalized) || normalized.includes(wardName);
    });
  }) || null;
}

async function syncAdministrativeCodesFromAddress(address) {
  const province = findProvinceByAddress(address);
  if (!province) return;

  if (String(province.code) !== form.provinceCode) {
    form.provinceCode = String(province.code);
    await fetchDistrictsByProvince(form.provinceCode);
  } else if (!districts.value.length) {
    await fetchDistrictsByProvince(form.provinceCode);
  }

  const district = findDistrictByAddress(address);
  if (!district) return;

  if (String(district.code) !== form.districtCode) {
    form.districtCode = String(district.code);
    await fetchWardsByDistrict(form.districtCode);
  } else if (!wards.value.length) {
    await fetchWardsByDistrict(form.districtCode);
  }

  const ward = findWardByAddress(address);
  if (!ward) return;
  form.wardCode = String(ward.code);
}

async function searchAddressOnMap() {
  const query = locationSearchText.value?.trim() || composeAddressQuery({ includeDetail: true });
  if (!query) return;
  await geocodeAddressToMap(query, 16);
}

async function fetchProvinces() {
  locationLoadError.value = "";
  try {
    const response = await fetch("https://provinces.open-api.vn/api/p/");
    if (!response.ok) {
      throw new Error("Không thể tải danh sách tỉnh/thành phố");
    }
    provinces.value = await response.json();
  } catch (error) {
    locationLoadError.value = error.message || "Không thể tải dữ liệu vị trí";
  }
}

async function fetchDistrictsByProvince(provinceCode) {
  locationLoadError.value = "";
  districtsLoading.value = true;
  try {
    const response = await fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=2`);
    if (!response.ok) {
      throw new Error("Không thể tải danh sách quận/huyện");
    }
    const data = await response.json();
    districts.value = data.districts || [];
  } catch (error) {
    locationLoadError.value = error.message || "Không thể tải dữ liệu quận/huyện";
  } finally {
    districtsLoading.value = false;
  }
}

async function fetchWardsByDistrict(districtCode) {
  locationLoadError.value = "";
  wardsLoading.value = true;
  try {
    const response = await fetch(`https://provinces.open-api.vn/api/d/${districtCode}?depth=2`);
    if (!response.ok) {
      throw new Error("Không thể tải danh sách phường/xã");
    }
    const data = await response.json();
    wards.value = data.wards || [];
  } catch (error) {
    locationLoadError.value = error.message || "Không thể tải dữ liệu phường/xã";
  } finally {
    wardsLoading.value = false;
  }
}

function onImagesChange(event) {
  const newFiles = event.target.files ? Array.from(event.target.files) : [];

  // Nếu người dùng Cancel, files rỗng → giữ nguyên ảnh cũ
  if (newFiles.length === 0) return;

  // Lấy danh sách key của ảnh đã có để tránh trùng lặp (dựa theo tên + size)
  const existingKeys = new Set(
    form.images.map((f) => (typeof f === 'string' ? f : `${f.name}_${f.size}`))
  );

  // Chỉ thêm những file chưa có trong danh sách
  const uniqueNewFiles = newFiles.filter(
    (f) => !existingKeys.has(`${f.name}_${f.size}`)
  );

  if (uniqueNewFiles.length === 0) {
    event.target.value = '';
    return;
  }

  // Giới hạn tối đa 10 ảnh
  const MAX = 10;
  const remaining = MAX - form.images.length;
  const filesToAdd = uniqueNewFiles.slice(0, remaining);

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

function removeImage(index) {
  const removed = imagePreviews.value[index];
  if (removed?.url) URL.revokeObjectURL(removed.url);
  imagePreviews.value = imagePreviews.value.filter((_, i) => i !== index);
  form.images = form.images.filter((_, i) => i !== index);
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

function onFrontCardChange(event) {
  if (frontCardPreviewUrl.value) {
    URL.revokeObjectURL(frontCardPreviewUrl.value);
  }
  form.identityCardFront = event.target.files?.[0] || null;
  frontCardPreviewUrl.value = form.identityCardFront ? URL.createObjectURL(form.identityCardFront) : "";
}

function onBackCardChange(event) {
  if (backCardPreviewUrl.value) {
    URL.revokeObjectURL(backCardPreviewUrl.value);
  }
  form.identityCardBack = event.target.files?.[0] || null;
  backCardPreviewUrl.value = form.identityCardBack ? URL.createObjectURL(form.identityCardBack) : "";
}

function onLegalDocumentsChange(event) {
  const files = event.target.files ? Array.from(event.target.files) : [];
  form.legalDocuments = files;
}

function toggleAppointmentDay(dayValue) {
  if (selectedAppointmentDays.value.includes(dayValue)) {
    selectedAppointmentDays.value = selectedAppointmentDays.value.filter((item) => item !== dayValue);
    return;
  }
  selectedAppointmentDays.value = [...selectedAppointmentDays.value, dayValue].sort((a, b) => a - b);
}

function toggleAllAppointmentDays() {
  if (isAllDaysSelected.value) {
    selectedAppointmentDays.value = [];
    return;
  }
  selectedAppointmentDays.value = appointmentDayOptions.map((day) => day.value);
}

function selectAppointmentTime(value) {
  appointmentTimeSlot.value = value;
  showTimeDropdown.value = false;
}

function buildNextAppointmentDateTime() {
  if (!selectedAppointmentDays.value.length || !appointmentTimeSlot.value) return "";

  const timeSlot = appointmentTimeOptions.find((slot) => slot.value === appointmentTimeSlot.value);
  if (!timeSlot) return "";

  const now = new Date();
  const candidateDates = selectedAppointmentDays.value.map((dayValue) => {
    const dayOffset = (dayValue - now.getDay() + 7) % 7;
    const date = new Date(now);
    date.setDate(now.getDate() + dayOffset);
    date.setHours(timeSlot.startHour, timeSlot.startMinute, 0, 0);

    if (date <= now) {
      date.setDate(date.getDate() + 7);
    }

    return date;
  });

  candidateDates.sort((a, b) => a.getTime() - b.getTime());
  const nextDate = candidateDates[0];
  if (!nextDate) return "";

  const pad = (value) => String(value).padStart(2, "0");
  return `${nextDate.getFullYear()}-${pad(nextDate.getMonth() + 1)}-${pad(nextDate.getDate())}T${pad(nextDate.getHours())}:${pad(nextDate.getMinutes())}`;
}

function setQuickNumber(field, value) {
  form[field] = value;
}

function setFurnitureStatus(status) {
  form.furnitureStatus = status;
}

function fieldError(field) {
  const validator = fieldValidators[field];
  return validator ? validator() : "";
}

function showFieldError(field) {
  return (submitAttempted.value || touchedFields.value[field]) && Boolean(fieldError(field));
}

function fieldErrorClass(field) {
  return showFieldError(field) ? "input-error" : "";
}

function touchField(field) {
  touchedFields.value = { ...touchedFields.value, [field]: true };
}

function validateAllFields() {
  const requiredFields = [
    "title",
    "description",
    "propertyType",
    "provinceCode",
    "districtCode",
    "wardCode",
    "area",
    "price",
    "bedrooms",
    "bathrooms",
    "floors",
    "floorNumber",
    "balconies",
    "facadeWidth",
    "depth",
    "contactName",
    "contactPhone",
  ];

  touchedFields.value = requiredFields.reduce((accumulator, field) => {
    accumulator[field] = true;
    return accumulator;
  }, {});

  return requiredFields.every((field) => !fieldError(field)) && !requiredImageError.value;
}

function blockNegativeNumberKey(event) {
  if (['-', '+', 'e', 'E'].includes(event.key)) {
    event.preventDefault();
  }
}

function blockPhoneKey(event) {
  const allowedKeys = [
    'Backspace',
    'Delete',
    'Tab',
    'ArrowLeft',
    'ArrowRight',
    'Home',
    'End',
    'Enter',
  ];

  if (allowedKeys.includes(event.key)) return;
  if (event.ctrlKey || event.metaKey) return;

  if (!/^[0-9]$/.test(event.key)) {
    event.preventDefault();
  }
}

function sanitizeNonNegativeNumber(field, event, allowDecimal = false) {
  const target = event?.target;
  if (!target) return;

  let value = String(target.value ?? '');
  value = value.replace(/-/g, '');

  if (allowDecimal) {
    value = value.replace(/[^0-9.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
      value = `${parts[0]}.${parts.slice(1).join('')}`;
    }
  } else {
    value = value.replace(/\D/g, '');
  }

  target.value = value;
  form[field] = value;
}

function sanitizePhoneNumber(event) {
  const target = event?.target;
  if (!target) return;

  const value = String(target.value ?? "").replace(/\D/g, "");
  target.value = value;
  form.contactPhone = value;
}

function toggleAmenity(amenity) {
  if (selectedAmenities.value.includes(amenity)) {
    selectedAmenities.value = selectedAmenities.value.filter((item) => item !== amenity);
    form.amenities = [...selectedAmenities.value];
    return;
  }
  selectedAmenities.value = [...selectedAmenities.value, amenity];
  form.amenities = [...selectedAmenities.value];
}

function pillClass(active) {
  return active
    ? "rounded-full bg-sky-500 px-4 py-1.5 text-xs font-semibold text-white"
    : "rounded-full bg-slate-100 px-4 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200";
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
  if (frontCardPreviewUrl.value) {
    URL.revokeObjectURL(frontCardPreviewUrl.value);
    frontCardPreviewUrl.value = "";
  }
  if (backCardPreviewUrl.value) {
    URL.revokeObjectURL(backCardPreviewUrl.value);
    backCardPreviewUrl.value = "";
  }
}

function resetForm() {
  clearImagePreviews();
  clearIdCardPreviews();
  Object.assign(form, createInitialState());
  submitAttempted.value = false;
  touchedFields.value = {};
  showLegalDropdown.value = false;
  showMoreDetail.value = true;
  showAppointmentSection.value = true;
  showVerificationSection.value = true;
  publicInfoAgreed.value = false;
  showDayDropdown.value = false;
  showTimeDropdown.value = false;
  selectedAppointmentDays.value = [];
  appointmentTimeSlot.value = "";
  selectedAmenities.value = [];
  submitError.value = "";
  validationErrors.value = {};
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
  loading.value = true;
  submitError.value = "";
  validationErrors.value = {};
  form.requestVerification = shouldRequestVerification.value;
  form.amenities = [...selectedAmenities.value];
  form.publicInfoAgreed = publicInfoAgreed.value;
  form.appointmentDays = [...selectedAppointmentDays.value];
  form.appointmentTimeSlot = appointmentTimeSlot.value;

  submitAttempted.value = true;

  if (!validateAllFields()) {
    submitError.value = "Vui lòng kiểm tra lại các trường bắt buộc.";
    loading.value = false;
    return;
  }

  try {
    // === Bước 1: Upload tất cả file lên Cloudinary ===
    const uploadMultiple = async (files) => {
      const urls = [];
      for (const file of files) {
        if (typeof file === 'string') { urls.push(file); continue; }
        const res = await cloudinaryService.uploadImage(file, 'listing');
        urls.push(res.secure_url);
      }
      return urls;
    };

    const uploadSingle = async (file) => {
      if (!file) return null;
      if (typeof file === 'string') return file;
      const res = await cloudinaryService.uploadImage(file, 'listing');
      return res.secure_url;
    };

    if (form.images && form.images.length > 0) {
      form.images = await uploadMultiple(form.images);
    }

    if (form.video) {
      form.video = await uploadSingle(form.video);
    }

    if (form.requestVerification) {
      if (form.identityCardFront) form.identityCardFront = await uploadSingle(form.identityCardFront);
      if (form.identityCardBack) form.identityCardBack = await uploadSingle(form.identityCardBack);
      if (form.legalDocuments?.length) form.legalDocuments = await uploadMultiple(form.legalDocuments);
    }

    // === Bước 2: Gửi JSON xuống Backend ===
    const response = await listingService.create(form);
    submitError.value = "";
    alert(response.data?.message || "Đăng tin thành công");
    resetForm();
  } catch (error) {
    if (error.response?.data) {
      const data = error.response.data;
      validationErrors.value = data?.errors || {};
      submitError.value = data?.message || "Gọi API thất bại. Kiểm tra token và dữ liệu.";
    } else {
      submitError.value = error.message || "Có lỗi xảy ra khi tải ảnh hoặc đăng tin.";
    }
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

.chevron.open {
  transform: rotate(180deg);
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
}

.legal-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
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

.more-info-arrow {
  margin-left: auto;
  color: #475569;
  transition: transform 0.2s ease;
}

.more-info-arrow.open {
  transform: rotate(180deg);
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
  background: #fff5f5;
}

.input-error:focus {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.field-error {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.35;
  color: #dc2626;
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
