<template>
  <footer class="bg-foreground text-primary-foreground/80 pt-16 pb-8">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
        <!-- Brand -->
        <div>
          <div class="flex items-center gap-2 mb-4">
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="32" height="32" rx="12" fill="url(#logo_footer)"/>
              <path d="M21.3337 14.6667C21.3337 17.9953 17.641 21.462 16.401 22.5327C16.2855 22.6195 16.1449 22.6665 16.0003 22.6665C15.8558 22.6665 15.7152 22.6195 15.5997 22.5327C14.3597 21.462 10.667 17.9953 10.667 14.6667C10.667 13.2522 11.2289 11.8956 12.2291 10.8954C13.2293 9.89525 14.5858 9.33334 16.0003 9.33334C17.4148 9.33334 18.7714 9.89525 19.7716 10.8954C20.7718 11.8956 21.3337 13.2522 21.3337 14.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 16.6667C17.1046 16.6667 18 15.7712 18 14.6667C18 13.5621 17.1046 12.6667 16 12.6667C14.8954 12.6667 14 13.5621 14 14.6667C14 15.7712 14.8954 16.6667 16 16.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <defs>
                <linearGradient id="logo_footer" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#0DA2E7"/>
                  <stop offset="1" stop-color="#0DA2E7"/>
                </linearGradient>
              </defs>
            </svg>
            <span class="text-lg font-bold text-primary-foreground">Propify</span>
          </div>
          <p class="text-sm leading-relaxed text-primary-foreground/60">
            Nền tảng cho thuê bất động sản uy tín hàng đầu Việt Nam. Kết nối chủ
            nhà và khách thuê một cách nhanh chóng.
          </p>
        </div>

        <!-- Khám phá -->
        <div>
          <h4 class="font-semibold text-primary-foreground mb-4">Khám phá</h4>
          <ul class="space-y-2 text-sm">
            <li v-for="item in exploreLinks" :key="item">
              <router-link to="/listings" class="hover:text-primary-foreground transition-colors">
                {{ item }}
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Hỗ trợ -->
        <div>
          <h4 class="font-semibold text-primary-foreground mb-4">Hỗ trợ</h4>
          <ul class="space-y-2 text-sm">
            <li v-for="item in supportLinks" :key="item">
              <router-link to="/" class="hover:text-primary-foreground transition-colors">
                {{ item }}
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Liên hệ -->
        <div>
          <h4 class="font-semibold text-primary-foreground mb-4">Liên hệ</h4>
          <ul class="space-y-3 text-sm">
            <li class="flex items-center gap-2">
              <span class="w-4 h-4 text-primary">📧</span>
              contact@renthouse.vn
            </li>
            <li class="flex items-center gap-2">
              <span class="w-4 h-4 text-primary">📞</span>
              0901 234 567
            </li>
            <li class="flex items-start gap-2">
              <span class="w-4 h-4 text-primary shrink-0 mt-0.5">📍</span>
              123 Nguyễn Huệ, Q.1, TP. HCM
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-primary-foreground/10 pt-6 text-center text-xs text-primary-foreground/40">
        © {{ currentYear }} Propify. Tất cả quyền được bảo lưu.
      </div>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear();

const exploreLinks = ['Căn hộ', 'Nhà phố', 'Biệt thự', 'Phòng trọ'];
const supportLinks = ['Hướng dẫn sử dụng', 'Chính sách bảo mật', 'Điều khoản dịch vụ', 'FAQ'];
</script>
