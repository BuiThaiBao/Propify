<?php

namespace App\Services\Payment\Export;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

final class ExcelTransactionExportStrategy implements TransactionExportStrategy
{
    public function export(Builder $query): Response
    {
        $transactions = $query->get();

        // Sử dụng view HTML Table gửi kèm header Excel
        $html = view('admin.exports.transactions_excel', compact('transactions'))->render();

        return new Response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="bao_cao_giao_dich_' . date('Ymd_His') . '.xls"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
