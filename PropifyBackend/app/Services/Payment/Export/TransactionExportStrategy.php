<?php

namespace App\Services\Payment\Export;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

interface TransactionExportStrategy
{
    /**
     * Xuất báo cáo giao dịch dựa trên câu truy vấn đã lọc.
     */
    public function export(Builder $query): Response;
}
