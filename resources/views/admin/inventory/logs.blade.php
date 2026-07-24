@extends('layouts.admin')

@section('title', 'Log Stok')
@section('page-title', 'Log Stok')
@section('page-subtitle', 'Telusuri seluruh perubahan persediaan dari stok masuk, penjualan, dan penyesuaian.')

@section('page-actions')
    <a href="{{ route('admin.inventory.adjustment') }}" class="btn btn-outline-secondary">
        Penyesuaian Stok
    </a>

    <a href="{{ route('admin.inventory.stock-in') }}" class="btn btn-primary">
        Stok Masuk
    </a>
@endsection

@section('content')
    @php
        $filterKeys = ['search', 'type', 'user_id', 'start_date', 'end_date'];

        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();

        $hasActiveFilters = $activeFilterCount > 0;

        $selectedUser = request()->filled('user_id') ? $users->firstWhere('id', (int) request('user_id')) : null;

        $typeLabels = [
            'in' => 'Stok Masuk',
            'out' => 'Stok Keluar',
            'adjustment' => 'Penyesuaian',
        ];

        $pageMovements = $stockMovements->getCollection();

        $pageStockInCount = $pageMovements->where('type', 'in')->count();

        $pageStockOutCount = $pageMovements->where('type', 'out')->count();

        $pageAdjustmentCount = $pageMovements
            ->filter(fn($movement) => in_array($movement->type, ['adjust', 'adjustment'], true))
            ->count();
    @endphp

    <div class="crud-page stock-log-page">
        <div class="stock-log-notice">
            <span class="stock-log-notice-icon">
                i
            </span>

            <div>
                <strong>Setiap perubahan stok tercatat otomatis</strong>

                <p>
                    Log menyimpan jumlah stok sebelum dan sesudah perubahan,
                    user pelaksana, jenis pergerakan, serta catatan audit.
                </p>
            </div>

            <span class="stock-log-notice-count">
                {{ number_format($stockMovements->total(), 0, ',', '.') }}
                log
            </span>
        </div>

        {{-- Filter --}}
        <section class="crud-panel stock-log-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Log Stok</h2>

                    <p>
                        Cari pergerakan stok berdasarkan produk, jenis,
                        user, catatan, atau tanggal.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form method="GET" action="{{ url()->current() }}">
                <div class="stock-log-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">
                                Cari Produk atau Catatan
                            </label>

                            <input type="search" name="search" id="search" class="form-control crud-form-control"
                                value="{{ request('search') }}" placeholder="Nama, SKU, barcode, atau catatan..."
                                autocomplete="off">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="type" class="form-label">
                                Jenis Pergerakan
                            </label>

                            <select name="type" id="type" class="form-select crud-form-control">
                                <option value="">
                                    Semua jenis
                                </option>

                                <option value="in" @selected(request('type') === 'in')>
                                    Stok Masuk
                                </option>

                                <option value="out" @selected(request('type') === 'out')>
                                    Stok Keluar
                                </option>

                                <option value="adjustment" @selected(request('type') === 'adjustment')>
                                    Penyesuaian
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="user_id" class="form-label">
                                User
                            </label>

                            <select name="user_id" id="user_id" class="form-select crud-form-control">
                                <option value="">
                                    Semua user
                                </option>

                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                                        {{ $user->name }}

                                        @if (!$user->is_active)
                                            — Nonaktif
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="start_date" class="form-label">
                                Tanggal Awal
                            </label>

                            <input type="date" name="start_date" id="start_date"
                                class="form-control crud-form-control
                                    @error('start_date') is-invalid @enderror"
                                value="{{ request('start_date') }}">

                            @error('start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="end_date" class="form-label">
                                Tanggal Akhir
                            </label>

                            <input type="date" name="end_date" id="end_date"
                                class="form-control crud-form-control
                                    @error('end_date') is-invalid @enderror"
                                value="{{ request('end_date') }}">

                            @error('end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12 stock-log-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                                    Hapus Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="stock-log-active-filters">
                    <span class="stock-log-filter-label">
                        Filter:
                    </span>

                    @if (request()->filled('search'))
                        <span class="stock-log-filter-chip">
                            Pencarian: {{ request('search') }}
                        </span>
                    @endif

                    @if (request()->filled('type'))
                        <span class="stock-log-filter-chip">
                            Jenis:
                            {{ $typeLabels[request('type')] ?? request('type') }}
                        </span>
                    @endif

                    @if ($selectedUser)
                        <span class="stock-log-filter-chip">
                            User: {{ $selectedUser->name }}
                        </span>
                    @endif

                    @if (request()->filled('start_date'))
                        <span class="stock-log-filter-chip">
                            Mulai:
                            {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}
                        </span>
                    @endif

                    @if (request()->filled('end_date'))
                        <span class="stock-log-filter-chip">
                            Sampai:
                            {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Table --}}
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Riwayat Pergerakan Stok</h2>

                    <p>
                        @if ($hasActiveFilters)
                            Menampilkan log berdasarkan filter yang diterapkan.
                        @else
                            Urutan log ditampilkan dari pergerakan paling baru.
                        @endif
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($stockMovements->total(), 0, ',', '.') }}
                    pergerakan
                </span>
            </div>

            <div class="stock-log-summary">
                <div>
                    <span>Ditampilkan</span>

                    <strong>
                        {{ number_format($stockMovements->count(), 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Stok Masuk di Halaman Ini</span>

                    <strong class="is-positive">
                        {{ number_format($pageStockInCount, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Stok Keluar di Halaman Ini</span>

                    <strong class="is-negative">
                        {{ number_format($pageStockOutCount, 0, ',', '.') }}
                    </strong>
                </div>

                <div>
                    <span>Penyesuaian di Halaman Ini</span>

                    <strong class="is-warning">
                        {{ number_format($pageAdjustmentCount, 0, ',', '.') }}
                    </strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table crud-table stock-log-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th>Perubahan</th>
                            <th>Pergerakan Stok</th>
                            <th>User</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($stockMovements as $movement)
                            @php
                                $productName = $movement->product?->name ?? 'Produk tidak ditemukan';

                                $previousStock = (int) $movement->previous_stock;
                                $newStock = (int) $movement->new_stock;
                                $quantityChange = (int) $movement->qty;

                                $movementLabel = match ($movement->type) {
                                    'in' => 'Stok Masuk',
                                    'out' => 'Stok Keluar',
                                    'adjust', 'adjustment' => 'Penyesuaian',
                                    default => ucfirst($movement->type ?? 'Lainnya'),
                                };

                                $movementClass = match ($movement->type) {
                                    'in' => 'is-in',
                                    'out' => 'is-out',
                                    'adjust', 'adjustment' => 'is-adjustment',
                                    default => 'is-default',
                                };

                                $differenceClass = match (true) {
                                    $quantityChange > 0 => 'is-positive',
                                    $quantityChange < 0 => 'is-negative',
                                    default => 'is-neutral',
                                };

                                $differenceText =
                                    $quantityChange > 0
                                        ? '+' . number_format($quantityChange, 0, ',', '.')
                                        : number_format($quantityChange, 0, ',', '.');
                            @endphp

                            <tr>
                                <td>
                                    <div class="stock-log-date">
                                        <strong>
                                            {{ $movement->created_at?->format('d M Y') ?? '-' }}
                                        </strong>

                                        <span>
                                            {{ $movement->created_at?->format('H:i:s') ?? '-' }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="stock-log-product-info">
                                        <strong>
                                            {{ $productName }}
                                        </strong>

                                        <span>
                                            SKU {{ $movement->product?->sku ?? '-' }}
                                        </span>

                                        <small>
                                            Produk #{{ $movement->product_id ?? '-' }}
                                            · Log #{{ $movement->id }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="crud-neutral-badge">
                                        {{ $movement->product?->category?->name ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="stock-log-type {{ $movementClass }}">
                                        {{ $movementLabel }}
                                    </span>
                                </td>

                                <td>
                                    <span class="stock-log-change {{ $differenceClass }}">
                                        {{ $differenceText }}
                                    </span>
                                </td>

                                <td>
                                    <div class="stock-log-flow">
                                        <div>
                                            <span>Sebelum</span>

                                            <strong>
                                                {{ number_format($previousStock, 0, ',', '.') }}
                                            </strong>
                                        </div>

                                        <span class="stock-log-flow-arrow">
                                            →
                                        </span>

                                        <div class="is-result">
                                            <span>Sesudah</span>

                                            <strong>
                                                {{ number_format($newStock, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="stock-log-user">
                                        <span class="stock-log-user-avatar">
                                            {{ strtoupper(mb_substr($movement->createdBy?->name ?? 'U', 0, 1)) }}
                                        </span>

                                        <div>
                                            <strong>
                                                {{ $movement->createdBy?->name ?? '-' }}
                                            </strong>

                                            <small>Pelaksana</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="stock-log-note">
                                        {{ $movement->note ?: 'Tidak ada catatan.' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            L
                                        </span>

                                        @if ($hasActiveFilters)
                                            <strong>Log stok tidak ditemukan</strong>

                                            <p>
                                                Tidak ada pergerakan stok yang sesuai
                                                dengan filter tersebut.
                                            </p>

                                            <a href="{{ url()->current() }}" class="btn btn-outline-primary btn-sm">
                                                Hapus Filter
                                            </a>
                                        @else
                                            <strong>Belum ada log stok</strong>

                                            <p>
                                                Pergerakan stok dari pembelian, penjualan,
                                                stok masuk, dan penyesuaian akan muncul di sini.
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
                </table>
            </div>

            @if ($stockMovements->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $stockMovements->firstItem() }}
                        sampai {{ $stockMovements->lastItem() }}
                        dari {{ $stockMovements->total() }} log
                    </span>

                    {{ $stockMovements->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
