<?php

namespace App\Exports\Sheets;

use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PurchaseSummarySheet implements FromArray, WithTitle, WithEvents, WithColumnWidths, WithColumnFormatting, ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;
    protected ?int $supplierId;
    protected ?string $status;
    protected int $lastRow = 1;

    public function __construct(string $startDate, string $endDate, ?int $supplierId = null, ?string $status = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->supplierId = $supplierId;
        $this->status = $status;
    }

    public function title(): string
    {
        return 'Ringkasan Pembelian';
    }

    public function array(): array
    {
        $supplierName = $this->supplierId
            ? Supplier::withTrashed()->find($this->supplierId)?->name
            : 'SEMUA';

        $statusLabel = $this->status ? strtoupper($this->status) : 'SEMUA';

        $baseQuery = Purchase::query()
            ->whereDate('purchase_date', '>=', $this->startDate)
            ->whereDate('purchase_date', '<=', $this->endDate)
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            });

        $tableQuery = (clone $baseQuery)
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            });

        $completedQuery = (clone $baseQuery)->where('status', 'completed');
        $voidQuery = (clone $baseQuery)->where('status', 'void');

        $totalTransactions = (clone $completedQuery)->count();
        $totalAmount = (clone $completedQuery)->sum('total');

        $totalQty = (clone $completedQuery)
            ->join('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->sum('purchase_items.qty');

        $totalVoidTransactions = (clone $voidQuery)->count();
        $totalVoidAmount = (clone $voidQuery)->sum('total');

        $rows = [
            ['RN BEER — LAPORAN PEMBELIAN', '', '', '', '', '', '', ''],
            [
                'Periode: ' . Carbon::parse($this->startDate)->format('d/m/Y') .
                ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y') .
                '     Supplier: ' . $supplierName .
                '     Status: ' . $statusLabel,
                '', '', '', '', '', '', '',
            ],
            ['', '', '', '', '', '', '', ''],
            ['RINGKASAN PEMBELIAN', '', '', '', '', '', '', ''],
            ['Total Transaksi', '', 'Total Item Dibeli', '', 'Total Pembelian', '', 'Pembelian Void', ''],
            [$totalTransactions, '', $totalQty, '', $totalAmount, '', $totalVoidTransactions, ''],
            ['', '', '', '', '', '', '', ''],
            ['DETAIL TRANSAKSI PEMBELIAN', '', '', '', '', '', '', ''],
            ['No', 'No. Pembelian', 'Tanggal', 'Supplier', 'Total Qty', 'Total Pembelian', 'Status', 'Input Oleh'],
        ];

        $purchases = $tableQuery
            ->with(['supplier', 'createdBy'])
            ->withSum('items as total_qty', 'qty')
            ->latest('purchase_date')
            ->latest('id')
            ->get();

        foreach ($purchases as $index => $purchase) {
            $rows[] = [
                $index + 1,
                $purchase->purchase_number,
                $purchase->purchase_date?->format('d/m/Y'),
                $purchase->supplier?->name ?? '-',
                $purchase->total_qty ?? 0,
                (float) $purchase->total,
                strtoupper($purchase->status),
                $purchase->createdBy?->name ?? '-',
            ];
        }

        if ($purchases->count() > 0) {
            $rows[] = [
                '',
                '',
                '',
                'TOTAL',
                $purchases->sum('total_qty'),
                (float) $purchases->sum('total'),
                '',
                '',
            ];
        }

        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['Total nilai pembelian void pada periode ini', '', '', '', (float) $totalVoidAmount, '', '', ''];

        $this->lastRow = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 24,
            'C' => 16,
            'D' => 28,
            'E' => 14,
            'F' => 20,
            'G' => 14,
            'H' => 22,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0',
            'F' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A4:H4');
                $sheet->mergeCells('A8:H8');
                $sheet->mergeCells('A5:B5');
                $sheet->mergeCells('C5:D5');
                $sheet->mergeCells('E5:F5');
                $sheet->mergeCells('G5:H5');
                $sheet->mergeCells('A6:B6');
                $sheet->mergeCells('C6:D6');
                $sheet->mergeCells('E6:F6');
                $sheet->mergeCells('G6:H6');

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:H4')->applyFromArray($this->sectionStyle());
                $sheet->getStyle('A8:H8')->applyFromArray($this->sectionStyle());

                $sheet->getStyle('A5:H6')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getStyle('A9:H9')->applyFromArray($this->headerStyle());

                if ($this->lastRow >= 10) {
                    $sheet->getStyle('A9:H' . $this->lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A10:A' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E10:E' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F10:F' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('G10:G' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }

    private function sectionStyle(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9EAF7'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAEAEA'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
    }
}