<?php

namespace App\Exports;

use App\Exports\Sheets\InventoryCategorySummarySheet;
use App\Exports\Sheets\InventoryStockSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoryReportExport implements WithMultipleSheets
{
    protected ?int $categoryId;

    protected ?string $status;

    public function __construct(?int $categoryId = null, ?string $status = null)
    {
        $this->categoryId = $categoryId;
        $this->status = $status;
    }

    public function sheets(): array
    {
        return [
            new InventoryStockSheet($this->categoryId, $this->status),
            new InventoryCategorySummarySheet($this->categoryId, $this->status),
        ];
    }
}
