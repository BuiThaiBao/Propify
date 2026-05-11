<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo hết hạn gói tin</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f8fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0284c7, #0ea5e9); padding: 32px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">Propify</h1>
                            <p style="margin: 8px 0 0; color: rgba(255,255,255,0.85); font-size: 14px;">Thông báo gói tin sắp hết hạn</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px 40px;">
                            <p style="margin: 0 0 16px; color: #334155; font-size: 16px; line-height: 1.6;">
                                Xin chào <strong>{{ $ownerName }}</strong>,
                            </p>

                            @if($daysLeft === 0)
                                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                    <p style="margin: 0; color: #dc2626; font-size: 15px; font-weight: 600;">
                                        ⚠️ Gói <strong>{{ $packageName }}</strong> của tin đăng sẽ hết hạn <strong>hôm nay</strong>!
                                    </p>
                                </div>
                            @else
                                <div style="background-color: #fefce8; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                    <p style="margin: 0; color: #ca8a04; font-size: 15px; font-weight: 600;">
                                        ⏰ Gói <strong>{{ $packageName }}</strong> của tin đăng sẽ hết hạn sau <strong>{{ $daysLeft }} ngày</strong>
                                    </p>
                                </div>
                            @endif

                            <!-- Listing Info -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                <p style="margin: 0 0 8px; font-size: 14px; color: #64748b;">Tin đăng:</p>
                                <p style="margin: 0 0 8px; font-size: 16px; font-weight: 600; color: #1e293b;">
                                    {{ $listing->title }}
                                </p>
                                <p style="margin: 0; font-size: 13px; color: #94a3b8;">
                                    Hết hạn lúc: {{ $expiresAt->format('H:i - d/m/Y') }}
                                </p>
                            </div>

                            <p style="margin: 0 0 24px; color: #475569; font-size: 14px; line-height: 1.6;">
                                Sau khi hết hạn, tin đăng sẽ mất các ưu đãi của gói (badge, ưu tiên hiển thị, boost điểm). 
                                Gia hạn ngay để tiếp tục được hưởng quyền lợi!
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin-bottom: 16px;">
                                <a href="{{ config('app.frontend_url', 'https://propify.vn') }}/profile?tab=listings&action=upgrade&listing={{ $listing->id }}"
                                   style="display: inline-block; background: linear-gradient(135deg, #0284c7, #0ea5e9); color: #ffffff; padding: 14px 40px; border-radius: 12px; text-decoration: none; font-size: 16px; font-weight: 600; box-shadow: 0 4px 14px rgba(2,132,199,0.35);">
                                    🔄 Gia hạn ngay
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                Email này được gửi tự động từ Propify. Bạn nhận email này vì có gói tin sắp hết hạn.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
