<?php

namespace App\Exports;

use App\Exports\Sheets\SalesDailySheet;
use App\Exports\Sheets\SalesPaymentMethodSheet;
use App\Exports\Sheets\SalesProductSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesReportExport implements WithMultipleSheets
{
    public function __construct(
        private string $startDate,
        private string $endDate,
        private ?string $paymentMethod,
        private ?string $status
    ) {}

    public function sheets(): array
    {
        return [
            new SalesDailySheet(
                $this->startDate,
                $this->endDate,
                $this->paymentMethod,
                $this->status
            ),

            new SalesProductSheet(
                $this->startDate,
                $this->endDate,
                $this->paymentMethod,
                $this->status
            ),

            new SalesPaymentMethodSheet(
                $this->startDate,
                $this->endDate,
                $this->paymentMethod,
                $this->status
            ),
        ];
    }
}