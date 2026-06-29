<?php

namespace App\Exports\Sheets;

use App\Models\Sale;
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

class SalesPaymentMethodSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        private string $startDate,
        private string $endDate,
        private ?string $paymentMethod,
        private ?string $status
    ) {}

    public function array(): array
    {
        $items = Sale::select(
                'payment_method',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total) as total_amount')
            )
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->where('status', 'completed')
            ->when($this->paymentMethod, fn ($q) => $q->where('payment_method', $this->paymentMethod))
            ->groupBy('payment_method')
            ->get();

        $grandTotal = $items->sum('total_amount');
        $grandTransactions = $items->sum('total_transactions');

        $rows = [];

        // Row 1
        $rows[] = [
            'RN BEER — REKAP METODE PEMBAYARAN',
            '', '', '', '',
        ];

        // Row 2
        $rows[] = [
            'Periode: ' . Carbon::parse($this->startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y')
            . '     Metode: ' . ($this->paymentMethod ? strtoupper($this->paymentMethod) : 'SEMUA')
            . '     Status: COMPLETED',
            '', '', '', '',
        ];

        // Row 3 pemisah
        $rows[] = ['', '', '', '', ''];

        // Row 4 section
        $rows[] = [
            'REKAP METODE PEMBAYARAN',
            '', '', '', '',
        ];

        // Row 5 header
        $rows[] = [
            'No',
            'Metode Pembayaran',
            'Jumlah Transaksi',
            'Total Nilai',
            '% dari Total',
        ];

        $no = 1;

        foreach ($items as $item) {
            $percentage = $grandTotal > 0
                ? ((float) $item->total_amount / (float) $grandTotal)
                : 0;

            $rows[] = [
                $no++,
                strtoupper($item->payment_method),
                (int) $item->total_transactions,
                (float) $item->total_amount,
                $percentage,
            ];
        }

        // Total row
        $rows[] = [
            '',
            'TOTAL',
            (int) $grandTransactions,
            (float) $grandTotal,
            1,
        ];

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '"Rp" #,##0',
            'E' => '0.0%',
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
                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A4:E4');
                $sheet->mergeCells('A' . $totalRow . ':B' . $totalRow);

                /*
                |--------------------------------------------------------------------------
                | Row 1 - Title
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A1:E1')->applyFromArray([
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
                $sheet->getStyle('A2:E2')->applyFromArray([
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
                $sheet->getStyle('A4:E4')->applyFromArray([
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
                $sheet->getStyle('A5:E5')->applyFromArray([
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
                    $sheet->getStyle('A6:E' . $highestRow)->applyFromArray([
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
                            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
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

                    $sheet->getStyle('C6:E' . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    $sheet->getStyle('C6:C' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle('D6:D' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');

                    $sheet->getStyle('E6:E' . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode('0.0%');
                }

                /*
                |--------------------------------------------------------------------------
                | Total Row
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A' . $totalRow . ':E' . $totalRow)->applyFromArray([
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

                $sheet->getStyle('C' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle('D' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                $sheet->getStyle('E' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.0%');

                /*
                |--------------------------------------------------------------------------
                | Column width fixed
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(22);
                $sheet->getColumnDimension('E')->setWidth(16);

                /*
                |--------------------------------------------------------------------------
                | Freeze, Filter, Print
                |--------------------------------------------------------------------------
                */
                $sheet->freezePane('A6');
                $sheet->setAutoFilter('A5:E' . ($highestRow - 1));

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getStyle('A1:E' . $highestRow)
                    ->getAlignment()
                    ->setWrapText(false);
            },
        ];
    }

    public function title(): string
    {
        return 'Metode Pembayaran';
    }
}