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

class PurchaseItemSheet implements FromArray, WithTitle, WithEvents, WithColumnWidths, WithColumnFormatting, ShouldAutoSize
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
        return 'Detail Item';
    }

    public function array(): array
    {
        $supplierName = $this->supplierId
            ? Supplier::withTrashed()->find($this->supplierId)?->name
            : 'SEMUA';

        $statusLabel = $this->status ? strtoupper($this->status) : 'SEMUA';

        $rows = [
            ['RN BEER — DETAIL ITEM PEMBELIAN', '', '', '', '', '', '', '', '', '', ''],
            [
                'Periode: ' . Carbon::parse($this->startDate)->format('d/m/Y') .
                ' s/d ' . Carbon::parse($this->endDate)->format('d/m/Y') .
                '     Supplier: ' . $supplierName .
                '     Status: ' . $statusLabel,
                '', '', '', '', '', '', '', '', '', '',
            ],
            ['', '', '', '', '', '', '', '', '', '', ''],
            ['DETAIL ITEM PEMBELIAN', '', '', '', '', '', '', '', '', '', ''],
            [
                'No',
                'No. Pembelian',
                'Tanggal',
                'Supplier',
                'Nama Produk',
                'SKU',
                'Kategori',
                'Qty',
                'Harga Modal',
                'Subtotal',
                'Status',
            ],
        ];

        $purchases = Purchase::query()
            ->with(['supplier', 'items.product.category'])
            ->whereDate('purchase_date', '>=', $this->startDate)
            ->whereDate('purchase_date', '<=', $this->endDate)
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest('purchase_date')
            ->latest('id')
            ->get();

        $number = 1;

        foreach ($purchases as $purchase) {
            foreach ($purchase->items as $item) {
                $rows[] = [
                    $number++,
                    $purchase->purchase_number,
                    $purchase->purchase_date?->format('d/m/Y'),
                    $purchase->supplier?->name ?? '-',
                    $item->product?->name ?? '-',
                    $item->product?->sku ?? '-',
                    $item->product?->category?->name ?? '-',
                    $item->qty,
                    (float) $item->cost_price,
                    (float) $item->subtotal,
                    strtoupper($purchase->status),
                ];
            }
        }

        if ($number > 1) {
            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                'TOTAL',
                collect(array_slice($rows, 5))->sum(fn ($row) => is_numeric($row[7] ?? null) ? $row[7] : 0),
                '',
                collect(array_slice($rows, 5))->sum(fn ($row) => is_numeric($row[9] ?? null) ? $row[9] : 0),
                '',
            ];
        }

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
            'E' => 30,
            'F' => 18,
            'G' => 20,
            'H' => 10,
            'I' => 18,
            'J' => 18,
            'K' => 14,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0',
            'I' => '"Rp" #,##0',
            'J' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:K1');
                $sheet->mergeCells('A2:K2');
                $sheet->mergeCells('A4:K4');

                $sheet->getStyle('A1:K1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:K2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:K4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAF7'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A5:K5')->applyFromArray([
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
                    $sheet->getStyle('A5:K' . $this->lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A6:A' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H6:H' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I6:J' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('K6:K' . $this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}