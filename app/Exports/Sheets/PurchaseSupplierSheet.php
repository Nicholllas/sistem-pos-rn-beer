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

class PurchaseSupplierSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithColumnWidths, WithEvents, WithTitle
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
        return 'Ringkasan Supplier';
    }

    public function array(): array
    {
        $supplierName = $this->supplierId
            ? Supplier::withTrashed()->find($this->supplierId)?->name
            : 'SEMUA';

        $rows = [
            ['RN BEER — RINGKASAN SUPPLIER', '', '', '', ''],
            [
                'Periode: '.Carbon::parse($this->startDate)->format('d/m/Y').
                ' s/d '.Carbon::parse($this->endDate)->format('d/m/Y').
                '     Supplier: '.$supplierName.
                '     Status: COMPLETED',
                '', '', '', '',
            ],
            ['', '', '', '', ''],
            ['RINGKASAN SUPPLIER', '', '', '', ''],
            ['No', 'Supplier', 'Total Transaksi', 'Total Qty', 'Total Pembelian'],
        ];

        $summary = Purchase::query()
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->leftJoin('purchase_items', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereDate('purchases.purchase_date', '>=', $this->startDate)
            ->whereDate('purchases.purchase_date', '<=', $this->endDate)
            ->where('purchases.status', 'completed')
            ->when($this->supplierId, function ($query) {
                $query->where('purchases.supplier_id', $this->supplierId);
            })
            ->selectRaw('
                suppliers.name as supplier_name,
                COUNT(DISTINCT purchases.id) as total_transactions,
                COALESCE(SUM(purchase_items.qty), 0) as total_qty,
                COALESCE(SUM(purchase_items.subtotal), 0) as total_amount
            ')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_amount')
            ->get();

        foreach ($summary as $index => $supplier) {
            $rows[] = [
                $index + 1,
                $supplier->supplier_name,
                $supplier->total_transactions,
                $supplier->total_qty,
                (float) $supplier->total_amount,
            ];
        }

        if ($summary->count() > 0) {
            $rows[] = [
                '',
                'TOTAL',
                $summary->sum('total_transactions'),
                $summary->sum('total_qty'),
                (float) $summary->sum('total_amount'),
            ];
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 32,
            'C' => 18,
            'D' => 14,
            'E' => 20,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A4:E4');

                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:E4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAF7'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A5:E5')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAEAEA'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                if ($this->lastRow >= 5) {
                    $sheet->getStyle('A5:E'.$this->lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A6:A'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C6:D'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E6:E'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
