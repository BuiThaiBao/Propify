<?php

namespace App\Services\Payment\Export;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

final class PdfTransactionExportStrategy implements TransactionExportStrategy
{
    public function export(Builder $query): Response
    {
        // Kiểm tra xem thư viện DomPDF đã được cài đặt chưa
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new BusinessException(
                ErrorCode::BadRequest,
                "Chức năng xuất PDF yêu cầu thư viện DomPDF. Vui lòng chạy lệnh 'composer require barryvdh/laravel-dompdf' tại thư mục Backend để cài đặt."
            );
        }

        $transactions = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.exports.transactions_pdf', compact('transactions'));
        $pdf->setPaper('a4', 'landscape');

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bao_cao_giao_dich_' . date('Ymd_His') . '.pdf"',
        ]);
    }
}
