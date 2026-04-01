<template>
  <footer class="bg-foreground text-primary-foreground/80 pt-16 pb-8">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
        <!-- Brand -->
        <div>
          <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg hero-gradient flex items-center justify-center">
              <span class="w-4 h-4 bg-white rounded-sm"></span>
            </div>
            <span class="text-lg font-bold text-primary-foreground">RentHouse</span>
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
        © {{ currentYear }} RentHouse. Tất cả quyền được bảo lưu.
      </div>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear();

const exploreLinks = ['Căn hộ', 'Nhà phố', 'Biệt thự', 'Phòng trọ'];
const supportLinks = ['Hướng dẫn sử dụng', 'Chính sách bảo mật', 'Điều khoản dịch vụ', 'FAQ'];
</script>
