<?php

namespace App\Exports\Sheets;

use App\Models\Sale;
use App\Models\SaleItem;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalesDailySheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithTitle
{
    private int $detailHeaderRow = 10;

    public function __construct(
        private string $startDate,
        private string $endDate,
        private ?string $paymentMethod,
        private ?string $status
    ) {}

    public function array(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Untuk sheet laporan harian, kita hitung laporan owner dari transaksi completed.
        | Void tidak masuk omzet, qty, pendapatan bersih, dan laba kotor.
        |--------------------------------------------------------------------------
        */
        $sales = Sale::with(['soldBy', 'items.product.category'])
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->where('status', 'completed')
            ->when($this->paymentMethod, fn ($q) => $q->where('payment_method', $this->paymentMethod))
            ->latest()
            ->get();

        $completedSaleIds = $sales->pluck('id');

        $totalTransactions = $sales->count();

        $totalItemsSold = SaleItem::whereIn('sale_id', $completedSaleIds)
            ->sum('qty');

        $totalPendapatan = $sales->sum('total');

        // Untuk sekarang belum ada modul expenses / operasional, jadi 0 dulu.
        $bebanOperasional = 0;

        $pendapatanBersih = $totalPendapatan - $bebanOperasional;

        $rows = [];

        // Row 1
        $rows[] = [
            'RN BEER — LAPORAN PENJUALAN HARIAN',
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 2
        $rows[] = [
            'Tanggal: '.Carbon::parse($this->startDate)->format('d/m/Y').' s/d '.Carbon::parse($this->endDate)->format('d/m/Y')
            .'     Kasir: SEMUA'
            .'     Shift: SEMUA',
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 3 pemisah
        $rows[] = ['', '', '', '', '', '', '', '', '', ''];

        // Row 4
        $rows[] = [
            'RINGKASAN PENJUALAN',
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 5
        $rows[] = [
            'Total Transaksi',
            '',
            'Total Item Terjual',
            '',
            'Total Pendapatan',
            '',
            'Beban Operasional',
            '',
            'Pendapatan Bersih',
            '',
        ];

        // Row 6 nilai ringkasan
        $rows[] = [
            $totalTransactions,
            '',
            $totalItemsSold,
            '',
            (float) $totalPendapatan,
            '',
            (float) $bebanOperasional,
            '',
            (float) $pendapatanBersih,
            '',
        ];

        // Row 7 dan 8 pemisah
        $rows[] = ['', '', '', '', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', '', '', '', ''];

        // Row 9
        $rows[] = [
            'DETAIL TRANSAKSI',
            '', '', '', '', '', '', '', '', '',
        ];

        // Row 10 header detail
        $rows[] = [
            'No',
            'No. Transaksi/Invoice',
            'Waktu Transaksi',
            'Nama Produk',
            'Kategori',
            'SKU',
            'Qty',
            'Harga Satuan Produk',
            'Harga Total Transaksi',
            'Laba Kotor',
        ];

        $no = 1;
        $totalQty = 0;
        $totalHargaTransaksi = 0;
        $totalLabaKotor = 0;

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $qty = (int) $item->qty;
                $hargaTotalTransaksi = (float) $item->subtotal;
                $labaKotor = (float) ($item->subtotal - ($item->qty * $item->cost_price));

                $totalQty += $qty;
                $totalHargaTransaksi += $hargaTotalTransaksi;
                $totalLabaKotor += $labaKotor;

                $rows[] = [
                    $no++,
                    $sale->invoice_number,
                    $sale->created_at?->format('H:i') ?? '-',
                    $item->product?->name ?? '-',
                    $item->product?->category?->name ?? '-',
                    $item->product?->sku ?? '-',
                    $qty,
                    (float) $item->price,
                    $hargaTotalTransaksi,
                    $labaKotor,
                ];
            }
        }

        // Row total detail
        $rows[] = [
            '',
            'TOTAL',
            '',
            '',
            '',
            '',
            $totalQty,
            '',
            (float) $totalHargaTransaksi,
            (float) $totalLabaKotor,
        ];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
            'E' => '"Rp" #,##0',
            'G' => '#,##0',
            'I' => '"Rp" #,##0',
            'H' => '"Rp" #,##0',
            'J' => '"Rp" #,##0',
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
                | Merge layout fixed sesuai template
                |--------------------------------------------------------------------------
                */
                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');

                $sheet->mergeCells('A4:J4');

                $sheet->mergeCells('A5:B5');
                $sheet->mergeCells('C5:D5');
                $sheet->mergeCells('E5:F5');
                $sheet->mergeCells('G5:H5');
                $sheet->mergeCells('I5:J5');

                $sheet->mergeCells('A6:B6');
                $sheet->mergeCells('C6:D6');
                $sheet->mergeCells('E6:F6');
                $sheet->mergeCells('G6:H6');
                $sheet->mergeCells('I6:J6');

                $sheet->mergeCells('A9:J9');

                $sheet->mergeCells('A'.$totalRow.':F'.$totalRow);

                /*
                |--------------------------------------------------------------------------
                | Row 1 - Title
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A1:J1')->applyFromArray([
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
                | Row 2 - Tanggal / Kasir / Shift
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A2:J2')->applyFromArray([
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
                | Row 4 - Ringkasan Penjualan
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A4:J4')->applyFromArray([
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
                | Row 5 - Summary Header
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A5:J5')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
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
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => $border],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(5)->setRowHeight(24);

                /*
                |--------------------------------------------------------------------------
                | Row 6 - Summary Values
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A6:J6')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => '002060'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $lightBlue],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => $blue],
                        ],
                    ],
                ]);

                $sheet->getStyle('E6:J6')
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                $sheet->getRowDimension(6)->setRowHeight(28);

                /*
                |--------------------------------------------------------------------------
                | Row 7-8 separator
                |--------------------------------------------------------------------------
                */
                $sheet->getRowDimension(7)->setRowHeight(16);
                $sheet->getRowDimension(8)->setRowHeight(16);

                /*
                |--------------------------------------------------------------------------
                | Row 9 - Detail Transaksi
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A9:J9')->applyFromArray([
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

                $sheet->getRowDimension(9)->setRowHeight(24);

                /*
                |--------------------------------------------------------------------------
                | Row 10 - Detail Header
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A10:J10')->applyFromArray([
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

                $sheet->getRowDimension(10)->setRowHeight(26);

                /*
                |--------------------------------------------------------------------------
                | Detail body row 11 dst
                |--------------------------------------------------------------------------
                */
                if ($highestRow > 10) {
                    $sheet->getStyle('A11:J'.$highestRow)->applyFromArray([
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

                    for ($row = 11; $row <= $highestRow - 1; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle('A'.$row.':J'.$row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F2F2F2'],
                                ],
                            ]);
                        }
                    }

                    $sheet->getStyle('A11:C'.$highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle('G11:J'.$highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle('H11:J'.$highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');
                }

                /*
                |--------------------------------------------------------------------------
                | Total row
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A'.$totalRow.':J'.$totalRow)->applyFromArray([
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

                $sheet->getStyle('I'.$totalRow.':J'.$totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                /*
                |--------------------------------------------------------------------------
                | Column width fixed
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(24);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(22);
                $sheet->getColumnDimension('G')->setWidth(8);
                $sheet->getColumnDimension('H')->setWidth(22);
                $sheet->getColumnDimension('I')->setWidth(22);
                $sheet->getColumnDimension('J')->setWidth(18);

                /*
                |--------------------------------------------------------------------------
                | Freeze, filter, print
                |--------------------------------------------------------------------------
                */
                $sheet->freezePane('A11');
                $sheet->setAutoFilter('A10:J'.($highestRow - 1));

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getStyle('A1:J'.$highestRow)
                    ->getAlignment()
                    ->setWrapText(false);
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Harian';
    }
}
