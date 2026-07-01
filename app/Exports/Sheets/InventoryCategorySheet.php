<?php

namespace App\Exports\Sheets;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventoryCategorySheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        private ?int $categoryId,
        private ?string $stockStatus
    ) {}

    public function array(): array
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->stockStatus === 'safe', fn ($q) => $q->whereColumn('stock', '>', 'minimum_stock'))
            ->when($this->stockStatus === 'low', fn ($q) => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock'))
            ->when($this->stockStatus === 'out', fn ($q) => $q->where('stock', 0))
            ->get();

        $categoryName = $this->categoryId
            ? Category::find($this->categoryId)?->name
            : 'SEMUA';

        $stockStatusLabel = match ($this->stockStatus) {
            'safe' => 'AMAN',
            'low' => 'MENIPIS',
            'out' => 'HABIS',
            default => 'SEMUA',
        };

        $grouped = $products->groupBy(fn ($product) => $product->category?->name ?? 'TANPA KATEGORI');

        $rows = [];

        // Row 1
        $rows[] = [
            'RN BEER — RINGKASAN STOK PER KATEGORI',
            '', '', '', '', '',
        ];

        // Row 2
        $rows[] = [
            'Tanggal Export: ' . Carbon::now()->format('d/m/Y H:i')
            . '     Kategori: ' . $categoryName
            . '     Status Stok: ' . $stockStatusLabel,
            '', '', '', '', '',
        ];

        // Row 3 separator
        $rows[] = ['', '', '', '', '', ''];

        // Row 4 section
        $rows[] = [
            'RINGKASAN KATEGORI',
            '', '', '', '', '',
        ];

        // Row 5 header
        $rows[] = [
            'No',
            'Kategori',
            'Total Produk',
            'Total Stok',
            'Nilai Modal',
            'Estimasi Jual',
            'Potensi Laba',
        ];

        $no = 1;
        $grandTotalProducts = 0;
        $grandTotalStock = 0;
        $grandCostValue = 0;
        $grandSalesValue = 0;
        $grandPotentialProfit = 0;

        foreach ($grouped as $category => $items) {
            /** @var Collection $items */
            $totalProducts = $items->count();
            $totalStock = $items->sum('stock');

            $costValue = $items->sum(fn ($product) => $product->stock * $product->cost_price);
            $salesValue = $items->sum(fn ($product) => $product->stock * $product->price);
            $potentialProfit = $salesValue - $costValue;

            $grandTotalProducts += $totalProducts;
            $grandTotalStock += $totalStock;
            $grandCostValue += $costValue;
            $grandSalesValue += $salesValue;
            $grandPotentialProfit += $potentialProfit;

            $rows[] = [
                $no++,
                $category,
                (int) $totalProducts,
                (int) $totalStock,
                (float) $costValue,
                (float) $salesValue,
                (float) $potentialProfit,
            ];
        }

        $rows[] = [
            '',
            'TOTAL',
            (int) $grandTotalProducts,
            (int) $grandTotalStock,
            (float) $grandCostValue,
            (float) $grandSalesValue,
            (float) $grandPotentialProfit,
        ];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '"Rp" #,##0',
            'F' => '"Rp" #,##0',
            'G' => '"Rp" #,##0',
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

                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');
                $sheet->mergeCells('A4:G4');
                $sheet->mergeCells('A' . $totalRow . ':B' . $totalRow);

                $sheet->getStyle('A1:G1')->applyFromArray([
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

                $sheet->getStyle('A2:G2')->applyFromArray([
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

                $sheet->getStyle('A4:G4')->applyFromArray([
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

                $sheet->getStyle('A5:G5')->applyFromArray([
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

                $sheet->getRowDimension(5)->setRowHeight(26);

                if ($highestRow > 5) {
                    $sheet->getStyle('A6:G' . $highestRow)->applyFromArray([
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
                            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F2F2F2'],
                                ],
                            ]);
                        }
                    }

                    $sheet->getStyle('A6:A' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle('C6:G' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle('C6:D' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('E6:G' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');
                }

                $sheet->getStyle('A' . $totalRow . ':G' . $totalRow)->applyFromArray([
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

                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(22);
                $sheet->getColumnDimension('F')->setWidth(22);
                $sheet->getColumnDimension('G')->setWidth(22);

                $sheet->freezePane('A6');
                $sheet->setAutoFilter('A5:G' . ($highestRow - 1));

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getStyle('A1:G' . $highestRow)
                    ->getAlignment()
                    ->setWrapText(false);
            },
        ];
    }

    public function title(): string
    {
        return 'Ringkasan Kategori';
    }
}