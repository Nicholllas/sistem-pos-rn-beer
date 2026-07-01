<?php

namespace App\Exports\Sheets;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventoryStockSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        private ?int $categoryId,
        private ?string $stockStatus
    ) {}

    public function array(): array
    {
        $productsQuery = Product::with(['category', 'lastStockUpdatedBy'])
            ->where('is_active', true)
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->stockStatus === 'safe', fn ($q) => $q->whereColumn('stock', '>', 'minimum_stock'))
            ->when($this->stockStatus === 'low', fn ($q) => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock'))
            ->when($this->stockStatus === 'out', fn ($q) => $q->where('stock', 0))
            ->orderBy('stock', 'asc')
            ->orderBy('name');

        $products = $productsQuery->get();

        $categoryName = $this->categoryId
            ? Category::find($this->categoryId)?->name
            : 'SEMUA';

        $stockStatusLabel = match ($this->stockStatus) {
            'safe' => 'AMAN',
            'low' => 'MENIPIS',
            'out' => 'HABIS',
            default => 'SEMUA',
        };

        $rows = [];

        // Row 1
        $rows[] = [
            'RN BEER — LAPORAN STOK PRODUK',
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 2
        $rows[] = [
            'Tanggal Export: ' . Carbon::now()->format('d/m/Y H:i')
            . '     Kategori: ' . $categoryName
            . '     Status Stok: ' . $stockStatusLabel,
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 3 separator
        $rows[] = ['', '', '', '', '', '', '', '', '', ''];

        // Row 4 section
        $rows[] = [
            'DETAIL STOK PRODUK',
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 5 header
        $rows[] = [
            'No',
            'Nama Produk',
            'SKU',
            'Kategori',
            'Harga Modal',
            'Harga Jual',
            'Stok',
            'Minimum',
            'Status',
            'Nilai Modal',
            'Estimasi Jual',
            'Potensi Laba',
            'Update Stok Oleh',
            'Update Stok Pada',
        ];

        $no = 1;
        $totalStock = 0;
        $totalCostValue = 0;
        $totalSalesValue = 0;
        $totalPotentialProfit = 0;

        foreach ($products as $product) {
            $stockCostValue = $product->stock * $product->cost_price;
            $stockSalesValue = $product->stock * $product->price;
            $stockProfitValue = $stockSalesValue - $stockCostValue;

            $status = 'AMAN';

            if ($product->stock == 0) {
                $status = 'HABIS';
            } elseif ($product->stock <= $product->minimum_stock) {
                $status = 'MENIPIS';
            }

            $totalStock += $product->stock;
            $totalCostValue += $stockCostValue;
            $totalSalesValue += $stockSalesValue;
            $totalPotentialProfit += $stockProfitValue;

            $rows[] = [
                $no++,
                $product->name,
                $product->sku,
                $product->category?->name ?? '-',
                (float) $product->cost_price,
                (float) $product->price,
                (int) $product->stock,
                (int) $product->minimum_stock,
                $status,
                (float) $stockCostValue,
                (float) $stockSalesValue,
                (float) $stockProfitValue,
                $product->lastStockUpdatedBy?->name ?? '-',
                $product->last_stock_updated_at?->format('d/m/Y H:i') ?? '-',
            ];
        }

        // Total row
        $rows[] = [
            '',
            'TOTAL',
            '',
            '',
            '',
            '',
            (int) $totalStock,
            '',
            '',
            (float) $totalCostValue,
            (float) $totalSalesValue,
            (float) $totalPotentialProfit,
            '',
            '',
        ];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'E' => '"Rp" #,##0',
            'F' => '"Rp" #,##0',
            'G' => '#,##0',
            'H' => '#,##0',
            'J' => '"Rp" #,##0',
            'K' => '"Rp" #,##0',
            'L' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow;

                $darkBlue = '1F3A66';
                $blue = '2E75B6';
                $lightBlue = 'D9EAF7';
                $white = 'FFFFFF';
                $border = 'BFBFBF';

                /*
                |--------------------------------------------------------------------------
                | Merge layout
                |--------------------------------------------------------------------------
                */
                $sheet->mergeCells('A1:N1');
                $sheet->mergeCells('A2:N2');
                $sheet->mergeCells('A4:N4');
                $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);

                /*
                |--------------------------------------------------------------------------
                | Row 1 - Title
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => $white],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $darkBlue],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(28);

                /*
                |--------------------------------------------------------------------------
                | Row 2 - Filter info
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A2:N2')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 11,
                        'color' => ['rgb' => '404040'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $lightBlue],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(16);

                /*
                |--------------------------------------------------------------------------
                | Row 4 - Section
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A4:N4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => $white],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $blue],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(4)->setRowHeight(24);

                /*
                |--------------------------------------------------------------------------
                | Row 5 - Header
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A5:N5')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => $white],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $darkBlue],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => $border],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(28);

                /*
                |--------------------------------------------------------------------------
                | Body
                |--------------------------------------------------------------------------
                */
                if ($highestRow > 5) {
                    $sheet->getStyle('A6:N' . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => $border],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    for ($row = 6; $row <= $highestRow - 1; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F2F2F2'],
                                ],
                            ]);
                        }

                        $status = $sheet->getCell('I' . $row)->getValue();

                        if ($status === 'HABIS') {
                            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
                                'font' => [
                                    'color' => ['rgb' => 'C00000'],
                                ],
                            ]);
                        } elseif ($status === 'MENIPIS') {
                            $sheet->getStyle('I' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFF2CC'],
                                ],
                                'font' => [
                                    'bold' => true,
                                    'color' => ['rgb' => '9C6500'],
                                ],
                            ]);
                        }
                    }

                    $sheet->getStyle('A6:A' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle('E6:H' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle('J6:L' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle('I6:I' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle('E6:F' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');

                    $sheet->getStyle('G6:H' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('J6:L' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');
                }

                /*
                |--------------------------------------------------------------------------
                | Total Row
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A' . $totalRow . ':N' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $white],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $darkBlue],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => $border],
                        ],
                    ],
                ]);

                $sheet->getStyle('G' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle('J' . $totalRow . ':L' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                /*
                |--------------------------------------------------------------------------
                | Column width fixed
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(34);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(10);
                $sheet->getColumnDimension('H')->setWidth(10);
                $sheet->getColumnDimension('I')->setWidth(14);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(20);
                $sheet->getColumnDimension('L')->setWidth(20);
                $sheet->getColumnDimension('M')->setWidth(24);
                $sheet->getColumnDimension('N')->setWidth(18);

                /*
                |--------------------------------------------------------------------------
                | Freeze, Filter, Print
                |--------------------------------------------------------------------------
                */
                $sheet->freezePane('A6');
                $sheet->setAutoFilter('A5:N' . ($highestRow - 1));

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getStyle('A1:N' . $highestRow)
                    ->getAlignment()
                    ->setWrapText(false);
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Stok';
    }
}