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
        .otp-box { text-align: center; margin: 28px 0; }
        .otp-code {
            display: inline-block;
            font-size: 40px;
            font-weight: 800;
            letter-spacing: 12px;
            color: #2563eb;
            background: #eff6ff;
            padding: 16px 32px;
            border-radius: 12px;
            border: 2px dashed #93c5fd;
        }
        .expire { color: #ef4444; font-size: 13px; margin-top: 12px; }
        .footer { margin-top: 32px; font-size: 12px; color: #9ca3af; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>Rent<span class="accent">House</span></span>
        </div>

        <h1>Xác thực tài khoản của bạn</h1>
        <p>Xin chào <strong>{{ $user->full_name }}</strong>,</p>
        <p>Bạn vừa đăng ký tài khoản tại RentHouse. Vui lòng nhập mã OTP bên dưới để hoàn tất xác thực:</p>

        <div class="otp-box">
            <div class="otp-code">{{ $data['otp'] }}</div>
            <p class="expire">⏱ Mã có hiệu lực trong <strong>3 phút</strong></p>
        </div>

        <p>Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này.</p>

        <div class="footer">
            © {{ date('Y') }} RentHouse. Tất cả quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
