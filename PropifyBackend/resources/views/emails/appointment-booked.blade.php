<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo đặt lịch xem nhà</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f8fc;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.06);overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0284c7,#0ea5e9);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">Propify</h1>
                            <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Thông báo đặt lịch xem nhà mới</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px;">
                            <p style="margin:0 0 16px;color:#334155;font-size:16px;line-height:1.6;">
                                Xin chào <strong>{{ $user->full_name ?? 'Quý khách' }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;color:#475569;font-size:14px;line-height:1.7;">
                                Bạn vừa nhận được một yêu cầu đặt lịch xem nhà cho tin đăng
                                <strong>{{ $data['listing_title'] ?? 'Tin đăng' }}</strong>.
                            </p>

                            <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
                                <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Khách đặt lịch</p>
                                <p style="margin:0 0 6px;font-size:16px;font-weight:600;color:#1e293b;">
                                    {{ $data['viewer_name'] ?? 'Không xác định' }}
                                </p>
                                <p style="margin:0 0 6px;font-size:14px;color:#475569;">
                                    SĐT: {{ $data['viewer_phone'] ?? 'Chưa cung cấp' }}
                                </p>
                                <p style="margin:0 0 6px;font-size:14px;color:#475569;">
                                    Email: {{ $data['viewer_email'] ?? 'Chưa cung cấp' }}
                                </p>
                                <p style="margin:0;font-size:14px;color:#475569;">
                                    Thời gian hẹn: <strong>{{ $data['meet_time'] ?? 'Chưa xác định' }}</strong>
                                </p>
                            </div>

                            <p style="margin:0;color:#475569;font-size:14px;line-height:1.7;">
                                Vui lòng truy cập trang cá nhân để xem chi tiết và phản hồi lịch hẹn này.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
