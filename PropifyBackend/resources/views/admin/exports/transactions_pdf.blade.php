<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo giao dịch</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 0.5px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">BÁO CÁO GIAO DỊCH HỆ THỐNG PROPIFY</div>
        <div>Ngày xuất: {{ date('d/m/Y H:i:s') }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">ID</th>
                <th style="width: 10%">Mã Hệ Thống</th>
                <th style="width: 10%">Mã VNPay</th>
                <th style="width: 12%">Khách Hàng</th>
                <th style="width: 15%">Email</th>
                <th style="width: 8%">Gói</th>
                <th style="width: 5%">Hạn (Ngày)</th>
                <th style="width: 8%">Số Tiền</th>
                <th style="width: 8%">Phương Thức</th>
                <th style="width: 8%">Trạng Thái</th>
                <th style="width: 11%">Ngày Giao Dịch</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->vnp_txn_ref }}</td>
                    <td>{{ $t->vnp_transaction_no }}</td>
                    <td>{{ $t->user?->full_name }}</td>
                    <td>{{ $t->user?->email }}</td>
                    <td>{{ $t->package?->name }}</td>
                    <td>{{ $t->duration_days }}</td>
                    <td>{{ number_format($t->amount) }} đ</td>
                    <td>{{ $t->payment_method }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->transaction_date?->format('d/m/Y H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="7" style="text-align: right; font-weight: bold;">TỔNG CỘNG DOANH THU:</td>
                <td style="font-weight: bold;">{{ number_format($transactions->sum('amount')) }} đ</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
