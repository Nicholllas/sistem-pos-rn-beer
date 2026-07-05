@extends('layouts.admin')

@section('title', 'Laporan Pembelian')
@section('page-title', 'Laporan Pembelian')

@section('content')

    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $totalPurchaseTransactions }}</h3>
                    <p>Total Transaksi Pembelian</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format($totalPurchaseAmount, 0, ',', '.') }}</h3>
                    <p>Total Pembelian</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $totalPurchaseItems }}</h3>
                    <p>Total Item Dibeli</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>{{ $totalVoidTransactions }}</h3>
                    <p>Pembelian Void</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                Filter Laporan Pembelian
            </h3>

            <a href="{{ route('admin.reports.purchases.export', request()->query()) }}" class="btn btn-success btn-sm">
                Export Excel
            </a>
        </div>

        <form method="GET" action="{{ route('admin.reports.purchases') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">
                            Tanggal Awal
                        </label>

                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $startDate }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">
                            Tanggal Akhir
                        </label>

                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $endDate }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="supplier_id" class="form-label">
                            Supplier
                        </label>

                        <select name="supplier_id" id="supplier_id" class="form-select">
                            <option value="">Semua Supplier</option>

                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $supplierId === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="completed" @selected($status === 'completed')>
                                Completed
                            </option>
                            <option value="void" @selected($status === 'void')>
                                Void
                            </option>
                        </select>
                    </div>

                    <div class="col-md-1 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            Filter
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('admin.reports.purchases') }}" class="btn btn-secondary btn-sm">
                        Reset Filter
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">
                        Detail Pembelian
                    </h3>

                    <span class="badge bg-info">
                        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                        s/d
                        {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    </span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">No</th>
                                    <th>No. Pembelian</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                    <th>Input Oleh</th>
                                    <th style="width: 90px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($purchases as $purchase)
                                    <tr>
                                        <td class="text-center">
                                            {{ $purchases->firstItem() + $loop->index }}
                                        </td>

                                        <td>
                                            <strong>{{ $purchase->purchase_number }}</strong>
                                        </td>

                                        <td class="text-nowrap">
                                            {{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}
                                        </td>

                                        <td>
                                            {{ $purchase->supplier?->name ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            {{ $purchase->total_qty ?? 0 }}
                                        </td>

                                        <td class="text-end text-nowrap">
                                            Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                        </td>

                                        <td class="text-center">
                                            @if ($purchase->status === 'completed')
                                                <span class="badge bg-success">COMPLETED</span>
                                            @else
                                                <span class="badge bg-danger">VOID</span>
                                            @endif
                                        </td>

                                        <td>
                                            <small>
                                                {{ $purchase->createdBy?->name ?? '-' }}
                                                <br>
                                                {{ $purchase->created_at?->format('d/m/Y H:i') ?? '-' }}
                                            </small>
                                        </td>

                                        <td class="text-center">
                                            <a href="{{ route('admin.purchases.show', $purchase) }}"
                                                class="btn btn-info btn-sm">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Tidak ada data pembelian pada filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            @if ($purchases->count() > 0)
                                @php
                                    $pagePurchases = $purchases->getCollection();
                                @endphp

                                <tfoot>
                                    <tr class="fw-bold">
                                        <th colspan="4" class="text-end">
                                            TOTAL HALAMAN INI
                                        </th>

                                        <th class="text-center">
                                            {{ $pagePurchases->sum('total_qty') }}
                                        </th>

                                        <th class="text-end text-nowrap">
                                            Rp {{ number_format($pagePurchases->sum('total'), 0, ',', '.') }}
                                        </th>

                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                @if ($purchases->hasPages())
                    <div class="card-footer">
                        {{ $purchases->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Ringkasan Supplier
                    </h3>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th class="text-center">Transaksi</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($supplierSummary as $supplier)
                                    <tr>
                                        <td>
                                            {{ $supplier->supplier_name }}
                                        </td>

                                        <td class="text-center">
                                            {{ $supplier->total_transactions }}
                                        </td>

                                        <td class="text-end text-nowrap">
                                            Rp {{ number_format($supplier->total_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            Belum ada pembelian completed.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                Ringkasan hanya menghitung pembelian dengan status <strong>completed</strong>.
                Pembelian void tidak dihitung sebagai total pembelian aktif.
            </div>

            @if ($totalVoidTransactions > 0)
                <div class="alert alert-warning">
                    Ada {{ $totalVoidTransactions }} pembelian void dengan total
                    <strong>Rp {{ number_format($totalVoidAmount, 0, ',', '.') }}</strong>
                    pada periode ini.
                </div>
            @endif
        </div>
    </div>

@endsection
