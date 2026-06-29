<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-size: 11px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>ID Giao Dịch</th>
                <th>Mã Tham Chiếu (vnp_txn_ref)</th>
                <th>Mã GD VNPay (vnp_transaction_no)</th>
                <th>Khách Hàng</th>
                <th>Email</th>
                <th>Số Điện Thoại</th>
                <th>Tin Đăng ID</th>
                <th>Tin Đăng</th>
                <th>Gói Dịch Vụ</th>
                <th>Thời Hạn (Ngày)</th>
                <th>Số Tiền</th>
                <th>Phương Thức</th>
                <th>Trạng Thái</th>
                <th>Mã Ngân Hàng</th>
                <th>Ngày Giao Dịch</th>
                <th>Ghi Chú Kế Toán</th>
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
                    <td>{{ $t->user?->phone }}</td>
                    <td>{{ $t->listing?->id }}</td>
                    <td>{{ $t->listing?->title }}</td>
                    <td>{{ $t->package?->name }}</td>
                    <td>{{ $t->duration_days }}</td>
                    <td>{{ $t->amount }}</td>
                    <td>{{ $t->payment_method }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->vnp_bank_code }}</td>
                    <td>{{ $t->transaction_date?->format('d/m/Y H:i:s') }}</td>
                    <td>
                        @php
                            $note = $t->notes->first();
                        @endphp
                        @if ($note)
                            [{{ $note->created_at?->format('d/m/Y H:i') }}] {{ $note->admin?->full_name }}: {{ $note->note }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="10" style="text-align: right; font-weight: bold;">TỔNG CỘNG DOANH THU:</td>
                <td style="font-weight: bold;">{{ number_format($transactions->sum('amount')) }} đ</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
