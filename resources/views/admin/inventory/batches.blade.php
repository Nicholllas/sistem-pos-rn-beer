@extends('layouts.admin')

@section('title', 'Batch FIFO')
@section('page-title', 'Batch FIFO')
@section('page-subtitle', 'Pantau lapisan persediaan, pemakaian batch, harga modal, dan nilai stok tersisa.')

@section('page-actions')
    <a href="{{ route('admin.inventory.logs') }}" class="btn btn-outline-secondary">
        Log Stok
    </a>

    <a href="{{ route('admin.inventory.stock-in') }}" class="btn btn-primary">
        Stok Masuk
    </a>
@endsection

@section('content')
    @php
        $filterKeys = ['product_id', 'source_type', 'status'];

        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();

        $hasActiveFilters = $activeFilterCount > 0;

        $selectedProduct = request()->filled('product_id')
            ? $products->firstWhere('id', (int) request('product_id'))
            : null;

        $statusLabels = [
            'active' => 'Masih memiliki stok',
            'empty' => 'Sudah habis',
        ];

        $pageBatches = $batches->getCollection();

        $pageQtyIn = $pageBatches->sum(fn($batch) => (int) $batch->qty_in);

        $pageQtyUsed = $pageBatches->sum(fn($batch) => max((int) $batch->qty_in - (int) $batch->qty_remaining, 0));

        $pageQtyRemaining = $pageBatches->sum(fn($batch) => (int) $batch->qty_remaining);

        $pageRemainingValue = $pageBatches->sum(fn($batch) => (int) $batch->qty_remaining * (float) $batch->cost_price);
    @endphp

    <div class="crud-page fifo-batch-page">

        {{-- Summary --}}
        <section class="fifo-summary-grid">
            <article class="fifo-summary-card">
                <span>Total Batch</span>

                <strong>
                    {{ number_format($totalBatches, 0, ',', '.') }}
                </strong>

                <small>
                    Berdasarkan filter saat ini
                </small>
            </article>

            <article class="fifo-summary-card">
                <span>Batch Aktif</span>

                <strong class="is-success">
                    {{ number_format($activeBatches, 0, ',', '.') }}
                </strong>

                <small>
                    Masih memiliki qty tersisa
                </small>
            </article>

            <article class="fifo-summary-card">
                <span>Batch Habis</span>

                <strong class="is-muted">
                    {{ number_format($emptyBatches, 0, ',', '.') }}
                </strong>

                <small>
                    Seluruh qty telah digunakan
                </small>
            </article>

            <article class="fifo-summary-card">
                <span>Nilai Sisa Persediaan</span>

                <strong class="is-warning">
                    Rp
                    {{ number_format($totalRemainingValue, 0, ',', '.') }}
                </strong>

                <small>
                    {{ number_format($totalRemainingQty, 0, ',', '.') }}
                    unit tersisa
                </small>
            </article>
        </section>

        {{-- Filter --}}
        <section class="crud-panel fifo-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Batch FIFO</h2>

                    <p>
                        Saring batch berdasarkan produk, sumber pembentukan,
                        atau ketersediaan stok.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.inventory.batches') }}">
                <div class="fifo-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-5">
                            <label for="product_id" class="form-label">
                                Produk
                            </label>

                            <select name="product_id" id="product_id"
                                class="form-select crud-form-control
                                    @error('product_id') is-invalid @enderror">
                                <option value="">
                                    Semua produk
                                </option>

                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>
                                        {{ $product->name }}
                                        · {{ $product->sku ?? '-' }}
                                    </option>
                                @endforeach
                            </select>

                            @error('product_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-lg-3">
                            <label for="source_type" class="form-label">
                                Sumber Batch
                            </label>

                            <select name="source_type" id="source_type"
                                class="form-select crud-form-control
                                    @error('source_type') is-invalid @enderror">
                                <option value="">
                                    Semua sumber
                                </option>

                                @foreach ($sourceLabels as $sourceType => $sourceLabel)
                                    <option value="{{ $sourceType }}" @selected(request('source_type') === $sourceType)>
                                        {{ $sourceLabel }}
                                    </option>
                                @endforeach
                            </select>

                            @error('source_type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="status" class="form-label">
                                Status
                            </label>

                            <select name="status" id="status"
                                class="form-select crud-form-control
                                    @error('status') is-invalid @enderror">
                                <option value="">
                                    Semua status
                                </option>

                                <option value="active" @selected(request('status') === 'active')>
                                    Masih ada stok
                                </option>

                                <option value="empty" @selected(request('status') === 'empty')>
                                    Sudah habis
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-2 fifo-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                Terapkan
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ route('admin.inventory.batches') }}" class="btn btn-outline-secondary">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="fifo-active-filters">
                    <span class="fifo-active-filter-label">
                        Filter:
                    </span>

                    @if ($selectedProduct)
                        <span class="fifo-filter-chip">
                            Produk: {{ $selectedProduct->name }}
                        </span>
                    @endif

                    @if (request()->filled('source_type'))
                        <span class="fifo-filter-chip">
                            Sumber:
                            {{ $sourceLabels[request('source_type')] ?? request('source_type') }}
                        </span>
                    @endif

                    @if (request()->filled('status'))
                        <span class="fifo-filter-chip">
                            Status:
                            {{ $statusLabels[request('status')] ?? request('status') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Batch table --}}
        <section class="crud-panel fifo-result-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Batch FIFO</h2>

                    <p>
                        Batch paling lama digunakan terlebih dahulu berdasarkan
                        tanggal batch, kemudian ID batch.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($totalRemainingQty, 0, ',', '.') }}
                    qty tersisa
                </span>
            </div>

            <div class="fifo-information-banner">
                <span>FIFO</span>

                <p>
                    Harga pokok penjualan mengikuti harga modal batch yang
                    dikonsumsi saat transaksi. Batch dengan tanggal paling lama
                    menjadi prioritas pertama.
                </p>
            </div>

            <div class="fifo-page-summary">
                <div>
                    <span>Batch Ditampilkan</span>

                    <strong>
                        {{ number_format($batches->count(), 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Qty Masuk</span>

                    <strong>
                        {{ number_format($pageQtyIn, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Qty Terpakai</span>

                    <strong class="is-warning">
                        {{ number_format($pageQtyUsed, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Qty Tersisa</span>

                    <strong class="is-success">
                        {{ number_format($pageQtyRemaining, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Nilai Sisa Halaman Ini</span>

                    <strong>
                        Rp
                        {{ number_format($pageRemainingValue, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table crud-table fifo-batch-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Batch</th>
                            <th>Sumber</th>
                            <th>Pemakaian FIFO</th>
                            <th>Harga Modal</th>
                            <th>Nilai Tersisa</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($batches as $batch)
                            @php
                                $qtyIn = max((int) $batch->qty_in, 0);

                                $qtyRemaining = max((int) $batch->qty_remaining, 0);

                                $usedQty = max($qtyIn - $qtyRemaining, 0);

                                $remainingValue = $qtyRemaining * (float) $batch->cost_price;

                                $usedPercentage = $qtyIn > 0 ? min(100, max(0, round(($usedQty / $qtyIn) * 100))) : 0;

                                $sourceLabel =
                                    $sourceLabels[$batch->source_type] ??
                                    ucfirst(str_replace('_', ' ', $batch->source_type ?? 'Lainnya'));

                                $sourceClass = match ($batch->source_type) {
                                    'initial_stock' => 'is-initial',
                                    'stock_in' => 'is-stock-in',
                                    'purchase' => 'is-purchase',
                                    'adjustment' => 'is-adjustment',
                                    'sale_void' => 'is-sale-void',
                                    default => 'is-default',
                                };

                                $batchStatus = match (true) {
                                    $qtyRemaining <= 0 => [
                                        'label' => 'Habis',
                                        'class' => 'is-empty',
                                    ],

                                    $qtyRemaining < $qtyIn => [
                                        'label' => 'Sebagian Terpakai',
                                        'class' => 'is-partial',
                                    ],

                                    default => [
                                        'label' => 'Aktif',
                                        'class' => 'is-active',
                                    ],
                                };
                            @endphp

                            <tr @class([
                                'is-empty' => $qtyRemaining <= 0,
                            ])>
                                <td>
                                    <div class="fifo-product-cell">
                                        <strong>
                                            {{ $batch->product?->name ?? 'Produk tidak ditemukan' }}
                                        </strong>

                                        <span>
                                            SKU {{ $batch->product?->sku ?? '-' }}
                                        </span>

                                        <small>
                                            {{ $batch->product?->category?->name ?? 'Tanpa kategori' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="fifo-batch-identity">
                                        <strong>
                                            Batch #{{ $batch->id }}
                                        </strong>

                                        <span>
                                            {{ $batch->batch_date?->format('d M Y') ?? '-' }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <span class="fifo-source-badge {{ $sourceClass }}">
                                        {{ $sourceLabel }}
                                    </span>

                                    @if ($batch->source_id)
                                        <small class="fifo-source-reference">
                                            Referensi #{{ $batch->source_id }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    <div class="fifo-usage-cell">
                                        <div class="fifo-usage-values">
                                            <div>
                                                <span>Masuk</span>
                                                <strong>
                                                    {{ number_format($qtyIn, 0, ',', '.') }}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Terpakai</span>
                                                <strong>
                                                    {{ number_format($usedQty, 0, ',', '.') }}
                                                </strong>
                                            </div>

                                            <div>
                                                <span>Tersisa</span>
                                                <strong class="is-remaining">
                                                    {{ number_format($qtyRemaining, 0, ',', '.') }}
                                                </strong>
                                            </div>
                                        </div>

                                        <div class="fifo-usage-progress" role="progressbar"
                                            aria-label="Pemakaian batch FIFO" aria-valuenow="{{ $usedPercentage }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                            <span style="width: {{ $usedPercentage }}%"></span>
                                        </div>

                                        <small>
                                            {{ $usedPercentage }}% telah digunakan
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="fifo-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($batch->cost_price, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            per unit
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="fifo-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($remainingValue, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            {{ number_format($qtyRemaining, 0, ',', '.') }}
                                            unit
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="fifo-status-badge {{ $batchStatus['class'] }}">
                                        {{ $batchStatus['label'] }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $batch->createdBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $batch->created_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crud-empty-state">

                                        @if ($hasActiveFilters)
                                            <strong>Batch tidak ditemukan</strong>

                                            <p>
                                                Tidak ada batch FIFO yang sesuai
                                                dengan kombinasi filter tersebut.
                                            </p>

                                            <a href="{{ route('admin.inventory.batches') }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Hapus Filter
                                            </a>
                                        @else
                                            <strong>Belum ada batch FIFO</strong>

                                            <p>
                                                Batch akan terbentuk dari stok awal,
                                                pembelian, stok masuk, penyesuaian,
                                                atau void penjualan.
                                            </p>

                                            <a href="{{ route('admin.inventory.stock-in') }}"
                                                class="btn btn-primary btn-sm">
                                                Tambah Stok
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($batches->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="3">
                                    Total halaman ini
                                </th>

                                <th>
                                    <div class="fifo-footer-quantity">
                                        <span>
                                            Masuk:
                                            <strong>
                                                {{ number_format($pageQtyIn, 0, ',', '.') }}
                                            </strong>
                                        </span>

                                        <span>
                                            Terpakai:
                                            <strong>
                                                {{ number_format($pageQtyUsed, 0, ',', '.') }}
                                            </strong>
                                        </span>

                                        <span>
                                            Tersisa:
                                            <strong>
                                                {{ number_format($pageQtyRemaining, 0, ',', '.') }}
                                            </strong>
                                        </span>
                                    </div>
                                </th>

                                <th></th>

                                <th>
                                    Rp
                                    {{ number_format($pageRemainingValue, 0, ',', '.') }}
                                </th>

                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if ($batches->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $batches->firstItem() }}
                        sampai {{ $batches->lastItem() }}
                        dari {{ $batches->total() }} batch
                    </span>

                    {{ $batches->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
