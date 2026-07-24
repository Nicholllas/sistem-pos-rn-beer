@extends('layouts.admin')

@section('title', 'Laporan Pembelian')
@section('page-title', 'Laporan Pembelian')
@section('page-subtitle', 'Analisis transaksi pembelian, jumlah stok masuk, supplier, dan nilai pengadaan.')

@section('page-actions')
    <a href="{{ route('admin.reports.purchases.export', request()->query()) }}" class="btn btn-success">
        Export Excel
    </a>
@endsection

@section('content')
    @php
        $resolvedStartDate = request('start_date', $startDate);
        $resolvedEndDate = request('end_date', $endDate);

        $filterKeys = ['start_date', 'end_date', 'supplier_id', 'status'];

        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();

        $hasActiveFilters = $activeFilterCount > 0;

        $selectedSupplier = $supplierId ? $suppliers->firstWhere('id', (int) $supplierId) : null;

        $statusLabels = [
            'completed' => 'Selesai',
            'void' => 'Void',
        ];

        $averagePurchase = $totalPurchaseTransactions > 0 ? $totalPurchaseAmount / $totalPurchaseTransactions : 0;

        $averageItemsPerPurchase =
            $totalPurchaseTransactions > 0 ? $totalPurchaseItems / $totalPurchaseTransactions : 0;

        $pagePurchases = $purchases->getCollection();

        $pageTotalQty = $pagePurchases->sum(fn($purchase) => (int) ($purchase->total_qty ?? 0));

        $pageTotalAmount = $pagePurchases->sum(fn($purchase) => (float) $purchase->total);
    @endphp

    <div class="crud-page purchase-report-page">

        {{-- Summary --}}
        <section class="purchase-report-summary">
            <article class="purchase-report-summary-card">
                <span>Transaksi Pembelian</span>

                <strong class="is-primary">
                    {{ number_format($totalPurchaseTransactions, 0, ',', '.') }}
                </strong>

                <small>
                    Pembelian selesai pada periode ini
                </small>
            </article>

            <article class="purchase-report-summary-card">
                <span>Total Pembelian</span>

                <strong class="is-success">
                    Rp
                    {{ number_format($totalPurchaseAmount, 0, ',', '.') }}
                </strong>

                <small>
                    Rata-rata Rp
                    {{ number_format($averagePurchase, 0, ',', '.') }}
                    per transaksi
                </small>
            </article>

            <article class="purchase-report-summary-card">
                <span>Total Item Dibeli</span>

                <strong class="is-warning">
                    {{ number_format($totalPurchaseItems, 0, ',', '.') }}
                </strong>

                <small>
                    Rata-rata
                    {{ number_format($averageItemsPerPurchase, 1, ',', '.') }}
                    item per pembelian
                </small>
            </article>

            <article class="purchase-report-summary-card">
                <span>Pembelian Void</span>

                <strong class="is-danger">
                    {{ number_format($totalVoidTransactions, 0, ',', '.') }}
                </strong>

                <small>
                    Rp
                    {{ number_format($totalVoidAmount, 0, ',', '.') }}
                    dibatalkan
                </small>
            </article>
        </section>

        {{-- Filter --}}
        <section class="crud-panel purchase-report-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Laporan</h2>

                    <p>
                        Tentukan periode, supplier, dan status pembelian
                        yang ingin dianalisis.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.reports.purchases') }}">
                <div class="purchase-report-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="start_date" class="form-label">
                                Tanggal Awal
                            </label>

                            <input type="date" name="start_date" id="start_date"
                                class="form-control crud-form-control
                                    @error('start_date') is-invalid @enderror"
                                value="{{ $resolvedStartDate }}">

                            @error('start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="end_date" class="form-label">
                                Tanggal Akhir
                            </label>

                            <input type="date" name="end_date" id="end_date"
                                class="form-control crud-form-control
                                    @error('end_date') is-invalid @enderror"
                                value="{{ $resolvedEndDate }}">

                            @error('end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-xl-4">
                            <label for="supplier_id" class="form-label">
                                Supplier
                            </label>

                            <select name="supplier_id" id="supplier_id"
                                class="form-select crud-form-control
                                    @error('supplier_id') is-invalid @enderror">
                                <option value="">
                                    Semua supplier
                                </option>

                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((string) $supplierId === (string) $supplier->id)>
                                        {{ $supplier->name }}

                                        @if (!$supplier->is_active)
                                            — Nonaktif
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            @error('supplier_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <label for="status" class="form-label">
                                Status
                            </label>

                            <select name="status" id="status"
                                class="form-select crud-form-control
                                    @error('status') is-invalid @enderror">
                                <option value="">
                                    Semua status
                                </option>

                                <option value="completed" @selected($status === 'completed')>
                                    Selesai
                                </option>

                                <option value="void" @selected($status === 'void')>
                                    Void
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="purchase-report-filter-footer">
                    <div class="purchase-report-period">
                        <span>Periode laporan</span>

                        <strong>
                            {{ \Carbon\Carbon::parse($resolvedStartDate)->format('d M Y') }}
                            –
                            {{ \Carbon\Carbon::parse($resolvedEndDate)->format('d M Y') }}
                        </strong>
                    </div>

                    <div class="purchase-report-filter-actions">
                        <a href="{{ route('admin.reports.purchases') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Tampilkan Laporan
                        </button>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="purchase-report-active-filters">
                    <span class="purchase-report-filter-label">
                        Filter:
                    </span>

                    @if (request()->filled('start_date'))
                        <span class="purchase-report-filter-chip">
                            Mulai:
                            {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if (request()->filled('end_date'))
                        <span class="purchase-report-filter-chip">
                            Sampai:
                            {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if ($selectedSupplier)
                        <span class="purchase-report-filter-chip">
                            Supplier: {{ $selectedSupplier->name }}
                        </span>
                    @endif

                    @if ($status)
                        <span class="purchase-report-filter-chip">
                            Status:
                            {{ $statusLabels[$status] ?? $status }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Detail purchases --}}
        <section class="crud-panel purchase-report-detail-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Detail Pembelian</h2>

                    <p>
                        Daftar transaksi pembelian berdasarkan periode
                        dan filter yang diterapkan.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($purchases->total(), 0, ',', '.') }}
                    pembelian
                </span>
            </div>

            <div class="purchase-report-result-summary">
                <div>
                    <span>Pembelian Ditampilkan</span>

                    <strong>
                        {{ number_format($purchases->count(), 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Qty Halaman Ini</span>

                    <strong>
                        {{ number_format($pageTotalQty, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Nilai Halaman Ini</span>

                    <strong>
                        Rp
                        {{ number_format($pageTotalAmount, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Halaman</span>

                    <strong>
                        {{ $purchases->currentPage() }}
                        dari
                        {{ $purchases->lastPage() }}
                    </strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table crud-table purchase-report-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pembelian</th>
                            <th>Supplier</th>
                            <th>Jumlah Barang</th>
                            <th>Total Pembelian</th>
                            <th>Status</th>
                            <th>Diinput Oleh</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($purchases as $purchase)
                            @php
                                $isVoid = $purchase->status === 'void';
                            @endphp

                            <tr @class([
                                'is-void' => $isVoid,
                            ])>
                                <td>
                                    <div class="purchase-report-number-cell">
                                        <a
                                            href="{{ route('admin.purchases.show', $purchase) }}">
                                            {{ $purchase->purchase_number }}
                                        </a>

                                        <span>
                                            {{ $purchase->purchase_date?->format('d M Y') ?? '-' }}
                                        </span>

                                        <small>
                                            Dicatat
                                            {{ $purchase->created_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-report-supplier-cell">
                                        <strong>
                                            {{ $purchase->supplier?->name ?? 'Supplier tidak ditemukan' }}
                                        </strong>

                                        <small>
                                            Supplier pembelian
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-report-qty-cell">
                                        <strong>
                                            {{ number_format($purchase->total_qty ?? 0, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            unit dibeli
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-report-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($purchase->total, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            {{ $isVoid ? 'Nilai dibatalkan' : 'Total pengadaan' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    @if ($purchase->status === 'completed')
                                        <span class="crud-status is-success">
                                            Selesai
                                        </span>
                                    @elseif ($purchase->status === 'void')
                                        <span class="crud-status is-danger">
                                            Void
                                        </span>
                                    @else
                                        <span class="crud-status is-muted">
                                            {{ ucfirst($purchase->status ?? '-') }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $purchase->createdBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $purchase->created_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.purchases.show', $purchase) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crud-empty-state">
                                        @if ($hasActiveFilters)
                                            <strong>Pembelian tidak ditemukan</strong>

                                            <p>
                                                Tidak ada transaksi pembelian
                                                yang sesuai dengan filter tersebut.
                                            </p>

                                            <a href="{{ route('admin.reports.purchases') }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Reset Filter
                                            </a>
                                        @else
                                            <strong>Belum ada pembelian</strong>

                                            <p>
                                                Transaksi pembelian stok akan
                                                ditampilkan di laporan ini.
                                            </p>

                                            <a href="{{ route('admin.purchases.create') }}"
                                                class="btn btn-primary btn-sm">
                                                Buat Pembelian
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($purchases->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="2">
                                    Total halaman ini
                                </th>

                                <th>
                                    {{ number_format($pageTotalQty, 0, ',', '.') }}
                                    unit
                                </th>

                                <th>
                                    Rp
                                    {{ number_format($pageTotalAmount, 0, ',', '.') }}
                                </th>

                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if ($purchases->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $purchases->firstItem() }}
                        sampai {{ $purchases->lastItem() }}
                        dari {{ $purchases->total() }} pembelian
                    </span>

                    {{ $purchases->withQueryString()->links() }}
                </div>
            @endif
        </section>

        {{-- Supplier summary --}}
        <section class="crud-panel purchase-supplier-summary-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Ringkasan Supplier</h2>

                    <p>
                        Kontribusi pembelian selesai berdasarkan supplier.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $supplierSummary->count() }} supplier
                </span>
            </div>

            <div class="purchase-supplier-notice">
                Ringkasan hanya menghitung transaksi dengan status selesai.
                Pembelian void tidak dimasukkan ke nominal pengadaan aktif.
            </div>

            <div class="table-responsive">
                <table class="table crud-table purchase-supplier-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Jumlah Transaksi</th>
                            <th>Total Pembelian</th>
                            <th>Rata-rata Pembelian</th>
                            <th>Kontribusi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($supplierSummary as $supplier)
                            @php
                                $averageSupplierPurchase =
                                    $supplier->total_transactions > 0
                                        ? $supplier->total_amount / $supplier->total_transactions
                                        : 0;

                                $supplierPercentage =
                                    $totalPurchaseAmount > 0
                                        ? min(100, max(0, ($supplier->total_amount / $totalPurchaseAmount) * 100))
                                        : 0;
                            @endphp

                            <tr>
                                <td>
                                    <div class="purchase-supplier-name">
                                        <strong>
                                            {{ $supplier->supplier_name }}
                                        </strong>

                                        <small>
                                            Supplier pengadaan
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-report-qty-cell">
                                        <strong>
                                            {{ number_format($supplier->total_transactions, 0, ',', '.') }}
                                        </strong>

                                        <small>transaksi</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-report-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($supplier->total_amount, 0, ',', '.') }}
                                        </strong>

                                        <small>total pembelian</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-report-money-cell">
                                        <strong>
                                            Rp
                                            {{ number_format($averageSupplierPurchase, 0, ',', '.') }}
                                        </strong>

                                        <small>per transaksi</small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-supplier-contribution">
                                        <div>
                                            <span>Kontribusi</span>

                                            <strong>
                                                {{ number_format($supplierPercentage, 1, ',', '.') }}%
                                            </strong>
                                        </div>

                                        <div class="purchase-supplier-progress">
                                            <span
                                                style="width:
                                                    {{ $supplierPercentage }}%"></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="crud-empty-state">
                                        <strong>Belum ada ringkasan supplier</strong>

                                        <p>
                                            Tidak ada pembelian selesai pada
                                            periode dan filter laporan ini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($totalVoidTransactions > 0)
            <div class="purchase-report-void-notice">
                <strong>
                    Terdapat
                    {{ number_format($totalVoidTransactions, 0, ',', '.') }}
                    pembelian void
                </strong>

                <p>
                    Total nominal pembelian yang dibatalkan pada periode ini
                    adalah
                    Rp
                    {{ number_format($totalVoidAmount, 0, ',', '.') }}.
                </p>
            </div>
        @endif
    </div>
@endsection
