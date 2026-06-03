<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật trạng thái lịch hẹn</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f8fc;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.06);overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0284c7,#0ea5e9);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">Propify</h1>
                            <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Cập nhật trạng thái lịch hẹn</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px;">
                            @if(($data['status'] ?? null) === 'APPROVED')
                                <p style="margin:0 0 16px;color:#166534;font-size:16px;font-weight:600;">
                                    Lịch hẹn xem nhà của bạn đã được chấp nhận.
                                </p>
                            @else
                                <p style="margin:0 0 16px;color:#b91c1c;font-size:16px;font-weight:600;">
                                    Lịch hẹn xem nhà của bạn đã bị từ chối.
                                </p>
                            @endif

                            <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
                                <p style="margin:0 0 8px;font-size:14px;color:#475569;">
                                    Tin đăng: <strong>{{ $data['listing_title'] ?? 'Tin đăng' }}</strong>
                                </p>
                                <p style="margin:0 0 8px;font-size:14px;color:#475569;">
                                    Thời gian hẹn: <strong>{{ $data['meet_time'] ?? 'Chưa xác định' }}</strong>
                                </p>
                                <p style="margin:0;font-size:14px;color:#475569;">
                                    Người phản hồi: <strong>{{ $data['poster_name'] ?? 'Người đăng tin' }}</strong>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
