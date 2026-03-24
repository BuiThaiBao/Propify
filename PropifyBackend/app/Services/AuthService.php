<?php

namespace App\Services;

// ====================================================================
// BƯỚC 5a: AuthService Interface
// ====================================================================
//
// GIẢI THÍCH:
// Tại sao dùng Interface thay vì code thẳng vào Controller?
//
// 1. TÁCH BIỆT (Separation of Concerns):
//    - Controller: chỉ xử lý HTTP (nhận request, trả response)
//    - Service: chứa business logic (tạo user, kiểm tra password, tạo token...)
//
// 2. DỄ THAY ĐỔI:
//    - Nếu sau này muốn đổi từ JWT sang Sanctum, chỉ cần tạo
//      AuthServiceSanctumImpl mới, không cần sửa Controller
//
// 3. DỄ TEST:
//    - Khi viết Unit Test cho Controller, có thể mock AuthService interface
//      mà không cần kết nối DB hay JWT thật
//
// LUỒNG: Controller → (gọi) → AuthService interface → (resolve) → AuthServiceImpl
// ====================================================================

interface AuthService
{
    public function login(array $credential);
    public function register(array $data);
    public function logout();
    public function refresh();
    public function me();
}
