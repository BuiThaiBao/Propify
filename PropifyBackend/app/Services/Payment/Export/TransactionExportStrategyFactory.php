<?php

namespace App\Services\Payment\Export;

use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;

final class TransactionExportStrategyFactory
{
    public function __construct(
        private readonly ExcelTransactionExportStrategy $excelStrategy,
        private readonly PdfTransactionExportStrategy $pdfStrategy,
    ) {}

    public function make(string $format): TransactionExportStrategy
    {
        return match (strtolower($format)) {
            'excel', 'xls', 'xlsx' => $this->excelStrategy,
            'pdf' => $this->pdfStrategy,
            default => throw new BusinessException(
                ErrorCode::BadRequest,
                "Định dạng xuất file {$format} không được hỗ trợ. Chỉ hỗ trợ excel hoặc pdf."
            ),
        };
    }
}
