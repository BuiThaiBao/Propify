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
            color: #7c3aed;
            background: #f5f3ff;
            padding: 16px 32px;
            border-radius: 12px;
            border: 2px dashed #c4b5fd;
        }
        .expire { color: #ef4444; font-size: 13px; margin-top: 12px; }
        .warning { background: #fef9c3; border: 1px solid #fde047; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #854d0e; margin-top: 20px; }
        .footer { margin-top: 32px; font-size: 12px; color: #9ca3af; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>Rent<span class="accent">House</span></span>
        </div>

        <h1>🔐 Đặt lại mật khẩu</h1>
        <p>Xin chào <strong>{{ $user->full_name }}</strong>,</p>
        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản liên kết với email này. Nhập mã OTP bên dưới để tiếp tục:</p>

        <div class="otp-box">
            <div class="otp-code">{{ $data['otp'] }}</div>
            <p class="expire">⏱ Mã có hiệu lực trong <strong>3 phút</strong></p>
        </div>

        <div class="warning">
            ⚠️ Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này. Tài khoản của bạn vẫn an toàn.
        </div>

        <div class="footer">
            © {{ date('Y') }} RentHouse. Tất cả quyền được bảo lưu.
        </div>
    </div>
</body>
</html>
