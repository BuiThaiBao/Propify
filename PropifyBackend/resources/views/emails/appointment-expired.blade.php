<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch hẹn quá thời gian xác nhận</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f8fc;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.06);overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#64748b,#0f172a);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">Propify</h1>
                            <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Thông báo lịch hẹn quá thời gian xác nhận</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px;">
                            <p style="margin:0 0 16px;color:#334155;font-size:16px;line-height:1.6;">
                                Xin chào <strong>{{ $user->full_name ?? 'Quý khách' }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;color:#475569;font-size:14px;line-height:1.7;">
                                Lịch hẹn xem nhà đã quá thời gian xác nhận và đã bị hủy tự động.
                            </p>

                            <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
                                <p style="margin:0 0 8px;font-size:14px;color:#475569;">
                                    Tin đăng: <strong>{{ $data['listing_title'] ?? 'Tin đăng' }}</strong>
                                </p>
                                <p style="margin:0 0 8px;font-size:14px;color:#475569;">
                                    Thời gian hẹn: <strong>{{ $data['meet_time'] ?? 'Chưa xác định' }}</strong>
                                </p>
                                <p style="margin:0 0 8px;font-size:14px;color:#475569;">
                                    Hạn xác nhận: <strong>{{ $data['confirm_deadline'] ?? 'Chưa xác định' }}</strong>
                                </p>
                                <p style="margin:0;font-size:14px;color:#475569;">
                                    Người đặt lịch: <strong>{{ $data['viewer_name'] ?? 'Khách xem nhà' }}</strong>
                                </p>
                            </div>

                            <p style="margin:0;color:#475569;font-size:14px;line-height:1.7;">
                                Bạn có thể đặt lại lịch hẹn khác nếu vẫn có nhu cầu xem tin đăng này.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
