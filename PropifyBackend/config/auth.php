<?php

// ====================================================================
// BƯỚC 3: Cấu hình Auth Guard
// ====================================================================
//
// GIẢI THÍCH:
// Laravel Auth system gồm 2 phần chính:
//
// 1. GUARD (Bảo vệ) - Xác định CÁCH xác thực:
//    - 'session': Dùng cookie + session (cho web truyền thống)
//    - 'jwt': Dùng JWT token trong header Authorization (cho API)
//    → Khi request đến, guard đọc token từ header → giải mã → tìm user
//
// 2. PROVIDER (Nhà cung cấp) - Xác định LẤY USER TỪ ĐÂU:
//    - 'eloquent': Dùng Eloquent model để query DB
//    - model => App\Models\Users::class: Dùng model Users
//    → Sau khi guard giải mã token lấy được user_id,
//      provider dùng Users::find($id) để lấy user object
//
// LUỒNG KHI GỌI Auth::attempt(['email'=>..., 'password'=>...]):
//    1. Provider tìm user bằng email: Users::where('email', $email)->first()
//    2. So sánh password: Hash::check($inputPassword, $user->password)
//    3. Nếu đúng → JWT Guard tạo token từ user
//    4. Trả về token string
//
// 'defaults' => ['guard' => 'api']:
//    → Khi gọi Auth::user() mà không chỉ định guard,
//      Laravel tự dùng guard 'api' (JWT) thay vì 'web' (session)
// ====================================================================

return [

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'jwt',         // Dùng JWT driver từ tymon/jwt-auth
            'provider' => 'users',      // Lấy user từ provider "users" bên dưới
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Users::class,
        ],
    ],

];
