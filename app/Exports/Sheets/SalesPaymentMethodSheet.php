<?php

namespace App\Exports\Sheets;

use App\Models\Sale;
use App\Models\SalePayment;
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

class SalesPaymentMethodSheet implements
    FromArray,
    ShouldAutoSize,
    WithTitle,
    WithEvents,
    WithColumnFormatting
{
    public function __construct(
        private string $startDate,
        private string $endDate,
        private ?string $paymentMethod,
        private ?string $status
    ) {}

    public function array(): array
    {
        $startDateTime = Carbon::parse($this->startDate)
            ->startOfDay();

        $endDateTime = Carbon::parse($this->endDate)
            ->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Ringkasan metode pembayaran utama
        |--------------------------------------------------------------------------
        |
        | Transaksi split tetap dihitung sebagai satu transaksi split.
        | Nominalnya menggunakan sales.total agar tidak terjadi double count.
        |
        */

        $items = Sale::query()
            ->whereBetween('created_at', [
                $startDateTime,
                $endDateTime,
            ])
            ->where('status', 'completed')
            ->when(
                $this->paymentMethod,
                fn ($query) => $query->where(
                    'payment_method',
                    $this->paymentMethod
                )
            )
            ->select('payment_method')
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('SUM(total) as total_amount')
            ->groupBy('payment_method')
            ->orderByRaw("
                CASE payment_method
                    WHEN 'cash' THEN 1
                    WHEN 'qris' THEN 2
                    WHEN 'transfer' THEN 3
                    WHEN 'split' THEN 4
                    ELSE 5
                END
            ")
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Rincian transaksi split
        |--------------------------------------------------------------------------
        |
        | Breakdown hanya perlu dihitung ketika:
        | - Semua metode dipilih; atau
        | - Filter metode adalah split.
        |
        */

        $splitPaymentBreakdown = collect([
            'cash' => 0,
            'qris' => 0,
            'transfer' => 0,
        ]);

        if (
            $this->paymentMethod === null
            || $this->paymentMethod === 'split'
        ) {
            $splitPaymentBreakdown = SalePayment::query()
                ->join(
                    'sales',
                    'sales.id',
                    '=',
                    'sale_payments.sale_id'
                )
                ->whereBetween('sales.created_at', [
                    $startDateTime,
                    $endDateTime,
                ])
                ->where('sales.status', 'completed')
                ->where('sales.payment_method', 'split')
                ->selectRaw('
                    sale_payments.payment_method,
                    SUM(sale_payments.amount) as total_amount
                ')
                ->groupBy('sale_payments.payment_method')
                ->pluck(
                    'total_amount',
                    'payment_method'
                );

            /*
             * Kembalian transaksi split berasal dari pembayaran cash.
             * Karena itu, kembalian harus mengurangi nominal cash
             * yang benar-benar masuk ke toko.
             */

            $totalSplitChange = Sale::query()
                ->whereBetween('created_at', [
                    $startDateTime,
                    $endDateTime,
                ])
                ->where('status', 'completed')
                ->where('payment_method', 'split')
                ->sum('change_amount');

            $splitCashAmount = max(
                0,
                (float) $splitPaymentBreakdown->get('cash', 0)
                    - (float) $totalSplitChange
            );

            $splitPaymentBreakdown->put(
                'cash',
                $splitCashAmount
            );

            /*
             * Pastikan semua key selalu tersedia.
             */

            $splitPaymentBreakdown->put(
                'qris',
                (float) $splitPaymentBreakdown->get('qris', 0)
            );

            $splitPaymentBreakdown->put(
                'transfer',
                (float) $splitPaymentBreakdown->get('transfer', 0)
            );
        }

        $grandTotal = (float) $items->sum('total_amount');

        $grandTransactions = (int) $items->sum(
            'total_transactions'
        );

        $rows = [];

        /*
        |--------------------------------------------------------------------------
        | Header laporan
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'RN BEER — REKAP METODE PEMBAYARAN',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        $rows[] = [
            'Periode: '
                . Carbon::parse($this->startDate)->format('d/m/Y')
                . ' s/d '
                . Carbon::parse($this->endDate)->format('d/m/Y')
                . '     Metode: '
                . (
                    $this->paymentMethod
                        ? strtoupper($this->paymentMethod)
                        : 'SEMUA'
                )
                . '     Status: COMPLETED',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        // Baris pemisah
        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        // Judul section
        $rows[] = [
            'REKAP METODE PEMBAYARAN',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        /*
        |--------------------------------------------------------------------------
        | Header tabel
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'No',
            'Metode Pembayaran',
            'Jumlah Transaksi',
            'Total Nilai',
            'Cash dalam Split',
            'QRIS dalam Split',
            'Transfer dalam Split',
            '% dari Total',
        ];

        $no = 1;

        /*
        |--------------------------------------------------------------------------
        | Detail metode pembayaran
        |--------------------------------------------------------------------------
        */

        foreach ($items as $item) {
            $isSplit = $item->payment_method === 'split';

            $percentage = $grandTotal > 0
                ? (
                    (float) $item->total_amount
                    / $grandTotal
                )
                : 0;

            $rows[] = [
                $no++,

                strtoupper(
                    $item->payment_method
                ),

                (int) $item->total_transactions,

                (float) $item->total_amount,

                $isSplit
                    ? (float) $splitPaymentBreakdown->get(
                        'cash',
                        0
                    )
                    : null,

                $isSplit
                    ? (float) $splitPaymentBreakdown->get(
                        'qris',
                        0
                    )
                    : null,

                $isSplit
                    ? (float) $splitPaymentBreakdown->get(
                        'transfer',
                        0
                    )
                    : null,

                $percentage,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            '',
            'TOTAL',
            $grandTransactions,
            $grandTotal,
            (float) $splitPaymentBreakdown->get('cash', 0),
            (float) $splitPaymentBreakdown->get('qris', 0),
            (float) $splitPaymentBreakdown->get('transfer', 0),
            $grandTotal > 0 ? 1 : 0,
        ];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '"Rp" #,##0',
            'E' => '"Rp" #,##0',
            'F' => '"Rp" #,##0',
            'G' => '"Rp" #,##0',
            'H' => '0.0%',
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

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A4:H4');

                $sheet->mergeCells(
                    'A' . $totalRow
                    . ':B' . $totalRow
                );

                /*
                |--------------------------------------------------------------------------
                | Row 1 - Title
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A1:H1')
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 16,
                            'color' => [
                                'rgb' => $white,
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => $darkBlue,
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                $sheet->getRowDimension(1)
                    ->setRowHeight(28);

                /*
                |--------------------------------------------------------------------------
                | Row 2 - Periode
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A2:H2')
                    ->applyFromArray([
                        'font' => [
                            'italic' => true,
                            'size' => 11,
                            'color' => [
                                'rgb' => '404040',
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => $lightBlue,
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                $sheet->getRowDimension(2)
                    ->setRowHeight(22);

                /*
                |--------------------------------------------------------------------------
                | Row 3 - Separator
                |--------------------------------------------------------------------------
                */

                $sheet->getRowDimension(3)
                    ->setRowHeight(16);

                /*
                |--------------------------------------------------------------------------
                | Row 4 - Section title
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A4:H4')
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 13,
                            'color' => [
                                'rgb' => $white,
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => $blue,
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                $sheet->getRowDimension(4)
                    ->setRowHeight(24);

                /*
                |--------------------------------------------------------------------------
                | Row 5 - Header
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A5:H5')
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11,
                            'color' => [
                                'rgb' => $white,
                            ],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => $darkBlue,
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => [
                                    'rgb' => $border,
                                ],
                            ],
                        ],
                    ]);

                $sheet->getRowDimension(5)
                    ->setRowHeight(38);

                /*
                |--------------------------------------------------------------------------
                | Body
                |--------------------------------------------------------------------------
                */

                if ($highestRow > 5) {
                    $sheet->getStyle(
                        'A6:H' . $highestRow
                    )->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => [
                                    'rgb' => $border,
                                ],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    for (
                        $row = 6;
                        $row <= $highestRow - 1;
                        $row++
                    ) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle(
                                'A'
                                . $row
                                . ':H'
                                . $row
                            )->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => [
                                        'rgb' => 'F2F2F2',
                                    ],
                                ],
                            ]);
                        }
                    }

                    $sheet->getStyle(
                        'A6:A' . $highestRow
                    )
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    $sheet->getStyle(
                        'C6:H' . $highestRow
                    )
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_RIGHT
                        );

                    $sheet->getStyle(
                        'C6:C' . $highestRow
                    )
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle(
                        'D6:G' . $highestRow
                    )
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');

                    $sheet->getStyle(
                        'H6:H' . $highestRow
                    )
                        ->getNumberFormat()
                        ->setFormatCode('0.0%');
                }

                /*
                |--------------------------------------------------------------------------
                | Total row
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle(
                    'A'
                    . $totalRow
                    . ':H'
                    . $totalRow
                )->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => [
                            'rgb' => $white,
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => $darkBlue,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [
                                'rgb' => $border,
                            ],
                        ],
                    ],
                ]);

                $sheet->getStyle(
                    'C' . $totalRow
                )
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle(
                    'D'
                    . $totalRow
                    . ':G'
                    . $totalRow
                )
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                $sheet->getStyle(
                    'H' . $totalRow
                )
                    ->getNumberFormat()
                    ->setFormatCode('0.0%');

                /*
                |--------------------------------------------------------------------------
                | Column width
                |--------------------------------------------------------------------------
                */

                $sheet->getColumnDimension('A')
                    ->setWidth(6);

                $sheet->getColumnDimension('B')
                    ->setWidth(24);

                $sheet->getColumnDimension('C')
                    ->setWidth(20);

                $sheet->getColumnDimension('D')
                    ->setWidth(22);

                $sheet->getColumnDimension('E')
                    ->setWidth(20);

                $sheet->getColumnDimension('F')
                    ->setWidth(20);

                $sheet->getColumnDimension('G')
                    ->setWidth(22);

                $sheet->getColumnDimension('H')
                    ->setWidth(16);

                /*
                |--------------------------------------------------------------------------
                | Freeze, Filter, Print
                |--------------------------------------------------------------------------
                */

                $sheet->freezePane('A6');

                if ($highestRow > 6) {
                    $sheet->setAutoFilter(
                        'A5:H' . ($highestRow - 1)
                    );
                }

                $sheet->getPageSetup()
                    ->setFitToWidth(1);

                $sheet->getPageSetup()
                    ->setFitToHeight(0);

                $sheet->getStyle(
                    'A1:H' . $highestRow
                )
                    ->getAlignment()
                    ->setWrapText(false);

                /*
                 * Header perlu wrap karena nama kolom cukup panjang.
                 */

                $sheet->getStyle('A5:H5')
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }

    public function title(): string
    {
        return 'Metode Pembayaran';
    }
}