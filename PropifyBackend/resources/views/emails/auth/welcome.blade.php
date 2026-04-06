<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo span { font-size: 22px; font-weight: 700; color: #1a1a2e; }
        .logo .accent { color: #2563eb; }
        h1 { font-size: 20px; color: #1a1a2e; margin-bottom: 8px; }
        p { color: #6b7280; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 24px; padding: 12px 28px; background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .footer { margin-top: 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>Rent<span class="accent">House</span></span>
        </div>

        <h1>Chào mừng, {{ $user->full_name }}! 🎉</h1>
        <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>RentHouse</strong> — nền tảng bất động sản hàng đầu.</p>
        <p>Tài khoản của bạn đã được tạo thành công. Hãy bắt đầu khám phá hàng nghìn bất động sản ngay hôm nay!</p>

        <a href="{{ config('app.frontend_url', config('app.url')) }}" class="btn">
            Khám phá ngay →
        </a>

        <div class="footer">
            © {{ date('Y') }} RentHouse. Tất cả quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
