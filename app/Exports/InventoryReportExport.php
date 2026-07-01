<?php

namespace App\Exports;

use App\Exports\Sheets\InventoryCategorySheet;
use App\Exports\Sheets\InventoryStockSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoryReportExport implements WithMultipleSheets
{
    public function __construct(
        private ?int $categoryId,
        private ?string $stockStatus
    ) {}

    public function sheets(): array
    {
        return [
            new InventoryStockSheet(
                $this->categoryId,
                $this->stockStatus
            ),

            new InventoryCategorySheet(
                $this->categoryId,
                $this->stockStatus
            ),
        ];
    }
}