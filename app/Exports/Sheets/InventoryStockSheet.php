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

class InventoryStockSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithColumnWidths, WithEvents, WithTitle
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
        return 'Laporan Stok FIFO';
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

        $products = Product::query()
            ->leftJoinSub($batchSummary, 'fifo_batches', function ($join) {
                $join->on('fifo_batches.product_id', '=', 'products.id');
            })
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
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
            ->select('products.*')
            ->selectRaw('COALESCE(categories.name, "-") as category_name')
            ->selectRaw('COALESCE(fifo_batches.fifo_stock_qty, 0) as fifo_stock_qty')
            ->selectRaw('COALESCE(fifo_batches.fifo_stock_value, 0) as fifo_stock_value')
            ->selectRaw('
                CASE
                    WHEN COALESCE(fifo_batches.fifo_stock_qty, 0) > 0
                    THEN COALESCE(fifo_batches.fifo_stock_value, 0) / COALESCE(fifo_batches.fifo_stock_qty, 0)
                    ELSE 0
                END as fifo_average_cost
            ')
            ->orderBy('products.name')
            ->get();

        $totalSystemStock = $products->sum('stock');
        $totalFifoStockQty = $products->sum('fifo_stock_qty');
        $totalFifoStockValue = $products->sum('fifo_stock_value');
        $totalLastCostValue = $products->sum(fn ($product) => $product->stock * $product->cost_price);
        $totalSalesValue = $products->sum(fn ($product) => $product->fifo_stock_qty * $product->price);
        $potentialGrossProfit = $totalSalesValue - $totalFifoStockValue;
        $stockMismatchCount = $products->filter(fn ($product) => (int) $product->stock !== (int) $product->fifo_stock_qty)->count();

        $rows = [
            ['RN BEER — LAPORAN STOK FIFO', '', '', '', '', '', '', '', '', '', '', '', ''],
            [
                'Kategori: '.$categoryName.
                '     Status: '.$statusLabel.
                '     Export: '.now()->format('d/m/Y H:i'),
                '', '', '', '', '', '', '', '', '', '', '', '',
            ],
            ['', '', '', '', '', '', '', '', '', '', '', '', ''],

            ['RINGKASAN STOK', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Total Produk', '', 'Stok Sistem', '', 'Stok FIFO', '', 'Produk Selisih Batch', '', 'Nilai FIFO', '', 'Nilai Last Cost', '', 'Selisih Nilai'],
            [
                $products->count(),
                '',
                $totalSystemStock,
                '',
                $totalFifoStockQty,
                '',
                $stockMismatchCount,
                '',
                (float) $totalFifoStockValue,
                '',
                (float) $totalLastCostValue,
                '',
                (float) ($totalLastCostValue - $totalFifoStockValue),
            ],
            ['', '', '', '', '', '', '', '', '', '', '', '', ''],

            ['DETAIL STOK PRODUK', '', '', '', '', '', '', '', '', '', '', '', ''],
            [
                'No',
                'Produk',
                'SKU',
                'Kategori',
                'Status Produk',
                'Stok Sistem',
                'Stok FIFO',
                'Minimum',
                'Harga Modal Terakhir',
                'Modal Rata-rata FIFO',
                'Harga Jual',
                'Nilai Modal FIFO',
                'Estimasi Nilai Jual',
                'Potensi Laba',
                'Status Stok',
                'Status Sinkron',
            ],
        ];

        foreach ($products as $index => $product) {
            $fifoStockQty = (int) $product->fifo_stock_qty;
            $fifoStockValue = (float) $product->fifo_stock_value;
            $fifoAverageCost = (float) $product->fifo_average_cost;
            $salesValue = $fifoStockQty * (float) $product->price;
            $profitValue = $salesValue - $fifoStockValue;

            if ((int) $product->stock <= 0) {
                $stockStatus = 'HABIS';
            } elseif ((int) $product->stock <= (int) $product->minimum_stock) {
                $stockStatus = 'MENIPIS';
            } else {
                $stockStatus = 'AMAN';
            }

            $syncStatus = (int) $product->stock === $fifoStockQty
                ? 'SINKRON'
                : 'SELISIH';

            $rows[] = [
                $index + 1,
                $product->name,
                $product->sku,
                $product->category_name,
                $product->is_active ? 'AKTIF' : 'NONAKTIF',
                (int) $product->stock,
                $fifoStockQty,
                (int) $product->minimum_stock,
                (float) $product->cost_price,
                $fifoAverageCost,
                (float) $product->price,
                $fifoStockValue,
                $salesValue,
                $profitValue,
                $stockStatus,
                $syncStatus,
            ];
        }

        if ($products->count() > 0) {
            $rows[] = [
                '',
                '',
                '',
                '',
                'TOTAL',
                $totalSystemStock,
                $totalFifoStockQty,
                '',
                '',
                '',
                '',
                (float) $totalFifoStockValue,
                (float) $totalSalesValue,
                (float) $potentialGrossProfit,
                '',
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
            'B' => 28,
            'C' => 16,
            'D' => 20,
            'E' => 16,
            'F' => 14,
            'G' => 14,
            'H' => 12,
            'I' => 20,
            'J' => 22,
            'K' => 18,
            'L' => 20,
            'M' => 20,
            'N' => 18,
            'O' => 14,
            'P' => 16,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '"Rp" #,##0',
            'J' => '"Rp" #,##0',
            'K' => '"Rp" #,##0',
            'L' => '"Rp" #,##0',
            'M' => '"Rp" #,##0',
            'N' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:P1');
                $sheet->mergeCells('A2:P2');
                $sheet->mergeCells('A4:P4');
                $sheet->mergeCells('A8:P8');

                $sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:P2')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A4:P4')->applyFromArray($this->sectionStyle());
                $sheet->getStyle('A8:P8')->applyFromArray($this->sectionStyle());

                $sheet->getStyle('A5:P6')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getStyle('A9:P9')->applyFromArray($this->headerStyle());

                if ($this->lastRow >= 10) {
                    $sheet->getStyle('A9:P'.$this->lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);

                    $sheet->getStyle('A10:A'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('E10:H'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I10:N'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('O10:P'.$this->lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->freezePane('A10');
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
