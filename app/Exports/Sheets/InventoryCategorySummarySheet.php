<?php

namespace App\Exports\Sheets;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBatch;
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

class InventoryCategorySummarySheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithColumnWidths, WithEvents, WithTitle
{
    protected ?int $categoryId;

    protected ?string $status;

    protected int $lastRow = 1;

    public function __construct(?int $categoryId = null, ?string $status = null)
    {
        $this->categoryId = $categoryId;
        $this->status = $status;
    }

    public function title(): string
    {
        return 'Ringkasan Kategori';
    }

    public function array(): array
    {
        $categoryName = $this->categoryId
            ? Category::withTrashed()->find($this->categoryId)?->name
            : 'SEMUA';

        $statusLabel = $this->status ? strtoupper(str_replace('_', ' ', $this->status)) : 'SEMUA';

        $batchSummary = StockBatch::query()
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(qty_remaining), 0) as fifo_stock_qty')
            ->selectRaw('COALESCE(SUM(qty_remaining * cost_price), 0) as fifo_stock_value')
            ->where('qty_remaining', '>', 0)
            ->groupBy('product_id');

        $summary = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoinSub($batchSummary, 'fifo_batches', function ($join) {
                $join->on('fifo_batches.product_id', '=', 'products.id');
            })
            ->when($this->categoryId, function ($query) {
                $query->where('products.category_id', $this->categoryId);
            })
            ->when($this->status, function ($query) {
                if ($this->status === 'active') {
                    $query->where('products.is_active', true);
                }

                if ($this->status === 'inactive') {
                    $query->where('products.is_active', false);
                }

                if ($this->status === 'low_stock') {
                    $query->whereColumn('products.stock', '<=', 'products.minimum_stock')
                        ->where('products.stock', '>', 0);
                }

                if ($this->status === 'out_of_stock') {
                    $query->where('products.stock', 0);
                }
            })
            ->selectRaw("
                COALESCE(categories.name, 'Tanpa Kategori') as category_name,
                COUNT(products.id) as total_products,
                COALESCE(SUM(products.stock), 0) as total_system_stock,
                COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_qty, 0)), 0) as total_fifo_stock,
                COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_value, 0)), 0) as total_fifo_value,
                COALESCE(SUM(products.stock * products.cost_price), 0) as total_last_cost_value,
                COALESCE(SUM(COALESCE(fifo_batches.fifo_stock_qty, 0) * products.price), 0) as total_sales_value
            ")
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        $rows = [
            ['RN BEER — RINGKASAN KATEGORI STOK FIFO', '', '', '', '', '', '', ''],
            [
                'Kategori: '.$categoryName.
                '     Status: '.$statusLabel.
                '     Export: '.now()->format('d/m/Y H:i'),
                '', '', '', '', '', '', '',
            ],
            ['', '', '', '', '', '', '', ''],
            ['RINGKASAN KATEGORI', '', '', '', '', '', '', ''],
            [
                'No',
                'Kategori',
                'Total Produk',
                'Stok Sistem',
                'Stok FIFO',
                'Nilai Modal FIFO',
                'Nilai Last Cost',
                'Estimasi Jual',
                'Potensi Laba',
            ],
        ];

        foreach ($summary as $index => $category) {
            $rows[] = [
                $index + 1,
                $category->category_name,
                $category->total_products,
                $category->total_system_stock,
                $category->total_fifo_stock,
                (float) $category->total_fifo_value,
                (float) $category->total_last_cost_value,
                (float) $category->total_sales_value,
                (float) $category->total_sales_value - (float) $category->total_fifo_value,
            ];
        }

        if ($summary->count() > 0) {
            $rows[] = [
                '',
                'TOTAL',
                $summary->sum('total_products'),
                $summary->sum('total_system_stock'),
                $summary->sum('total_fifo_stock'),
                (float) $summary->sum('total_fifo_value'),
                (float) $summary->sum('total_last_cost_value'),
                (float) $summary->sum('total_sales_value'),
                (float) ($summary->sum('total_sales_value') - $summary->sum('total_fifo_value')),
            ];
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 28,
            'C' => 16,
            'D' => 16,
            'E' => 16,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 18,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '#,##0',
            'F' => '"Rp" #,##0',
            'G' => '"Rp" #,##0',
            'H' => '"Rp" #,##0',
            'I' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:I1');
                $sheet->mergeCells('A2:I2');
                $sheet->mergeCells('A4:I4');

                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:I2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:I4')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAF7'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A5:I5')->applyFromArray([
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
                    $sheet->getStyle('A5:I'.$this->lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A6:A'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C6:E'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F6:I'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->freezePane('A6');
            },
        ];
    }
}
