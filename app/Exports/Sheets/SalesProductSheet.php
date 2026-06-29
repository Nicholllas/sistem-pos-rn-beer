<?php

namespace App\Exports\Sheets;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalesProductSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        private string $startDate,
        private string $endDate,
        private ?string $paymentMethod,
        private ?string $status
    ) {}

    public function array(): array
    {
        $completedSaleIds = Sale::whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->where('status', 'completed')
            ->when($this->paymentMethod, fn ($q) => $q->where('payment_method', $this->paymentMethod))
            ->pluck('id');

        $grandTotal = SaleItem::whereIn('sale_id', $completedSaleIds)
            ->sum('subtotal');

        $items = SaleItem::select(
                'products.name as product_name',
                'products.sku',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.qty) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_sales')
            )
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('sale_items.sale_id', $completedSaleIds)
            ->groupBy('products.id', 'products.name', 'products.sku', 'categories.name')
            ->orderByDesc('total_qty')
            ->get();

        $rows = [];

        // Row 1
        $rows[] = [
            'RN BEER — REKAP PENJUALAN PER PRODUK',
            '', '', '', '', '', '',
        ];

        // Row 2
        $rows[] = [
            'Periode: ' . Carbon::parse($this->startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y')
            . '     Metode: ' . ($this->paymentMethod ? strtoupper($this->paymentMethod) : 'SEMUA')
            . '     Status: COMPLETED',
            '', '', '', '', '', '',
        ];

        // Row 3 pemisah
        $rows[] = ['', '', '', '', '', '', ''];

        // Row 4 section
        $rows[] = [
            'REKAP PRODUK TERJUAL',
            '', '', '', '', '', '',
        ];

        // Row 5 header
        $rows[] = [
            'No',
            'Nama Produk',
            'SKU',
            'Kategori',
            'Total Qty',
            'Total Penjualan',
            '% dari Total',
        ];

        $no = 1;

        foreach ($items as $item) {
            $percentage = $grandTotal > 0
                ? ((float) $item->total_sales / (float) $grandTotal)
                : 0;

            $rows[] = [
                $no++,
                $item->product_name,
                $item->sku,
                $item->category_name ?? '-',
                (int) $item->total_qty,
                (float) $item->total_sales,
                $percentage,
            ];
        }

        // Total row
        $rows[] = [
            '',
            'TOTAL',
            '',
            '',
            (int) $items->sum('total_qty'),
            (float) $grandTotal,
            1,
        ];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0',
            'F' => '"Rp" #,##0',
            'G' => '0.0%',
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
                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');
                $sheet->mergeCells('A4:G4');
                $sheet->mergeCells('A' . $totalRow . ':D' . $totalRow);

                /*
                |--------------------------------------------------------------------------
                | Row 1 - Title
                |--------------------------------------------------------------------------
                */
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

                /*
                |--------------------------------------------------------------------------
                | Row 2 - Periode
                |--------------------------------------------------------------------------
                */
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

                /*
                |--------------------------------------------------------------------------
                | Row 3 separator
                |--------------------------------------------------------------------------
                */
                $sheet->getRowDimension(3)->setRowHeight(16);

                /*
                |--------------------------------------------------------------------------
                | Row 4 - Section title
                |--------------------------------------------------------------------------
                */
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

                /*
                |--------------------------------------------------------------------------
                | Row 5 - Header
                |--------------------------------------------------------------------------
                */
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

                /*
                |--------------------------------------------------------------------------
                | Body
                |--------------------------------------------------------------------------
                */
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

                    $sheet->getStyle('E6:G' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle('E6:E' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('F6:F' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');

                    $sheet->getStyle('G6:G' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('0.0%');
                }

                /*
                |--------------------------------------------------------------------------
                | Total Row
                |--------------------------------------------------------------------------
                */
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

                $sheet->getStyle('E' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle('F' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                $sheet->getStyle('G' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.0%');

                /*
                |--------------------------------------------------------------------------
                | Column width fixed
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(34);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(22);
                $sheet->getColumnDimension('G')->setWidth(16);

                /*
                |--------------------------------------------------------------------------
                | Freeze, Filter, Print
                |--------------------------------------------------------------------------
                */
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
        return 'Rekap Produk';
    }
}