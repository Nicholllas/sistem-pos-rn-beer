<?php

namespace App\Exports;

use App\Exports\Sheets\PurchaseItemSheet;
use App\Exports\Sheets\PurchaseSummarySheet;
use App\Exports\Sheets\PurchaseSupplierSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PurchaseReportExport implements WithMultipleSheets
{
    protected string $startDate;

    protected string $endDate;

    protected ?int $supplierId;

    protected ?string $status;

    public function __construct(string $startDate, string $endDate, ?int $supplierId = null, ?string $status = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->supplierId = $supplierId;
        $this->status = $status;
    }

    public function sheets(): array
    {
        return [
            new PurchaseSummarySheet($this->startDate, $this->endDate, $this->supplierId, $this->status),
            new PurchaseItemSheet($this->startDate, $this->endDate, $this->supplierId, $this->status),
            new PurchaseSupplierSheet($this->startDate, $this->endDate, $this->supplierId, $this->status),
        ];
    }
}
