@extends('layouts.admin')

@section('title', 'Batch FIFO')
@section('page-title', 'Batch FIFO')

@section('content')

    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $totalBatches }}</h3>
                    <p>Total Batch</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ $activeBatches }}</h3>
                    <p>Batch Aktif</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-secondary">
                <div class="inner">
                    <h3>{{ $emptyBatches }}</h3>
                    <p>Batch Habis</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>Rp {{ number_format($totalRemainingValue, 0, ',', '.') }}</h3>
                    <p>Nilai Sisa Stok</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Filter Batch FIFO
            </h3>
        </div>

        <form method="GET" action="{{ route('admin.inventory.batches') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="product_id" class="form-label">
                            Produk
                        </label>

                        <select name="product_id" id="product_id" class="form-select">
                            <option value="">Semua Produk</option>

                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>
                                    {{ $product->name }} - {{ $product->sku ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="source_type" class="form-label">
                            Sumber Batch
                        </label>

                        <select name="source_type" id="source_type" class="form-select">
                            <option value="">Semua Sumber</option>

                            @foreach ($sourceLabels as $sourceType => $sourceLabel)
                                <option value="{{ $sourceType }}" @selected(request('source_type') === $sourceType)>
                                    {{ $sourceLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">
                            Status Batch
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active" @selected(request('status') === 'active')>
                                Aktif / Masih Ada Stok
                            </option>
                            <option value="empty" @selected(request('status') === 'empty')>
                                Habis
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Filter
                        </button>

                        <a href="{{ route('admin.inventory.batches') }}" class="btn btn-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                Daftar Batch FIFO
            </h3>

            <span class="badge bg-info">
                Sisa Qty: {{ $totalRemainingQty }}
            </span>
        </div>

        <div class="card-body">
            <div class="alert alert-info">
                Halaman ini menampilkan layer stok FIFO. Penjualan akan mengambil stok dari batch paling lama
                berdasarkan tanggal batch dan ID batch.
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>Produk</th>
                            <th class="text-center">Tanggal Batch</th>
                            <th>Sumber</th>
                            <th class="text-center">Qty In</th>
                            <th class="text-center">Terpakai</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-end">Harga Modal</th>
                            <th class="text-end">Nilai Sisa</th>
                            <th class="text-center">Status</th>
                            <th>Dibuat Oleh</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($batches as $batch)
                            @php
                                $usedQty = $batch->qty_in - $batch->qty_remaining;
                                $remainingValue = $batch->qty_remaining * $batch->cost_price;
                                $sourceLabel = $sourceLabels[$batch->source_type] ?? $batch->source_type;
                            @endphp

                            <tr>
                                <td class="text-center">
                                    {{ $batches->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <strong>{{ $batch->product?->name ?? '-' }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        SKU: {{ $batch->product?->sku ?? '-' }}
                                        |
                                        Kategori: {{ $batch->product?->category?->name ?? '-' }}
                                    </small>
                                </td>

                                <td class="text-center text-nowrap">
                                    {{ $batch->batch_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $sourceLabel }}
                                    </span>

                                    @if ($batch->source_id)
                                        <br>
                                        <small class="text-muted">
                                            Ref ID: {{ $batch->source_id }}
                                        </small>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ $batch->qty_in }}
                                </td>

                                <td class="text-center">
                                    {{ $usedQty }}
                                </td>

                                <td class="text-center">
                                    {{ $batch->qty_remaining }}
                                </td>

                                <td class="text-end text-nowrap">
                                    Rp {{ number_format($batch->cost_price, 0, ',', '.') }}
                                </td>

                                <td class="text-end text-nowrap">
                                    Rp {{ number_format($remainingValue, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    @if ($batch->qty_remaining <= 0)
                                        <span class="badge bg-secondary">HABIS</span>
                                    @elseif ($batch->qty_remaining < $batch->qty_in)
                                        <span class="badge bg-warning">SEBAGIAN</span>
                                    @else
                                        <span class="badge bg-success">AKTIF</span>
                                    @endif
                                </td>

                                <td>
                                    <small>
                                        {{ $batch->createdBy?->name ?? '-' }}
                                        <br>
                                        {{ $batch->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Belum ada data batch FIFO.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($batches->count() > 0)
                        @php
                            $pageBatches = $batches->getCollection();
                        @endphp

                        <tfoot>
                            <tr class="fw-bold">
                                <th colspan="4" class="text-end">TOTAL HALAMAN INI</th>

                                <th class="text-center">
                                    {{ $pageBatches->sum('qty_in') }}
                                </th>

                                <th class="text-center">
                                    {{ $pageBatches->sum(fn($batch) => $batch->qty_in - $batch->qty_remaining) }}
                                </th>

                                <th class="text-center">
                                    {{ $pageBatches->sum('qty_remaining') }}
                                </th>

                                <th></th>

                                <th class="text-end text-nowrap">
                                    Rp
                                    {{ number_format($pageBatches->sum(fn($batch) => $batch->qty_remaining * $batch->cost_price), 0, ',', '.') }}
                                </th>

                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @if ($batches->hasPages())
            <div class="card-footer">
                {{ $batches->links() }}
            </div>
        @endif
    </div>

@endsection
