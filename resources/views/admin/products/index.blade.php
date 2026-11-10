@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')
@section('page-subtitle', 'Kelola identitas, harga, stok minimum, dan status produk.')

@section('page-actions')
    <a href="{{ route('admin.products.trash') }}" class="btn btn-outline-secondary">
        Produk Terhapus
    </a>

    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        Tambah Produk
    </a>
@endsection

@section('content')
    @php
        $filterKeys = ['search', 'category_id', 'packaging_type', 'stock_status', 'is_active'];
        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();
        $hasActiveFilters = $activeFilterCount > 0;

        $selectedCategory = request()->filled('category_id') ? $categories->firstWhere('id', (int) request('category_id')) : null;

        $stockStatusLabels = [
            'in_stock' => 'Stok Aman',
            'low_stock' => 'Stok Menipis',
            'out_of_stock' => 'Stok Habis (0)',
        ];
    @endphp

    <div class="crud-page">
        {{-- Filter Panel --}}
        <section class="crud-panel product-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter & Pencarian Produk</h2>
                    <p>
                        Saring produk berdasarkan kata kunci, kategori, jenis kemasan, atau status stok.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.products.index') }}" class="product-filter-form">
                <div class="product-filter-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4 col-lg-3">
                            <label for="search" class="form-label">
                                Kata Kunci
                            </label>
                            <input type="search" name="search" id="search"
                                class="form-control crud-form-control"
                                value="{{ request('search') }}"
                                placeholder="Nama, brand, SKU, barcode..."
                                autocomplete="off">
                        </div>

                        <div class="col-6 col-md-4 col-lg-2">
                            <label for="category_id" class="form-label">
                                Kategori
                            </label>
                            <select name="category_id" id="category_id" class="form-select crud-form-control">
                                <option value="">Semua kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-4 col-lg-2">
                            <label for="packaging_type" class="form-label">
                                Jenis Kemasan
                            </label>
                            <select name="packaging_type" id="packaging_type" class="form-select crud-form-control">
                                <option value="">Semua kemasan</option>
                                <option value="BOTOL BESAR" @selected(request('packaging_type') === 'BOTOL BESAR')>Botol Besar</option>
                                <option value="BOTOL KECIL" @selected(request('packaging_type') === 'BOTOL KECIL')>Botol Kecil</option>
                                <option value="KALENG BESAR" @selected(request('packaging_type') === 'KALENG BESAR')>Kaleng Besar</option>
                                <option value="KALENG KECIL" @selected(request('packaging_type') === 'KALENG KECIL')>Kaleng Kecil</option>
                                <option value="none" @selected(request('packaging_type') === 'none')>Tanpa Kemasan</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3 col-lg-2">
                            <label for="stock_status" class="form-label">
                                Status Stok
                            </label>
                            <select name="stock_status" id="stock_status" class="form-select crud-form-control">
                                <option value="">Semua stok</option>
                                <option value="in_stock" @selected(request('stock_status') === 'in_stock')>Stok Aman</option>
                                <option value="low_stock" @selected(request('stock_status') === 'low_stock')>Stok Menipis</option>
                                <option value="out_of_stock" @selected(request('stock_status') === 'out_of_stock')>Stok Habis (0)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-3 col-lg-1">
                            <label for="is_active" class="form-label">
                                Status
                            </label>
                            <select name="is_active" id="is_active" class="form-select crud-form-control">
                                <option value="">Semua</option>
                                <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                                <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-2 product-filter-actions">
                            <button type="submit" class="btn btn-primary w-100">
                                Cari
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary" title="Reset filter pencarian">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($hasActiveFilters)
                    <div class="product-active-filters">
                        <span class="product-active-filter-label">Filter Aktif:</span>

                        @if (request()->filled('search'))
                            <span class="product-filter-chip">
                                Kata kunci: "{{ request('search') }}"
                            </span>
                        @endif

                        @if ($selectedCategory)
                            <span class="product-filter-chip">
                                Kategori: {{ $selectedCategory->name }}
                            </span>
                        @endif

                        @if (request()->filled('packaging_type'))
                            <span class="product-filter-chip">
                                Kemasan: {{ request('packaging_type') === 'none' ? 'Tanpa Kemasan' : request('packaging_type') }}
                            </span>
                        @endif

                        @if (request()->filled('stock_status'))
                            <span class="product-filter-chip">
                                {{ $stockStatusLabels[request('stock_status')] ?? request('stock_status') }}
                            </span>
                        @endif

                        @if (request()->filled('is_active'))
                            <span class="product-filter-chip">
                                Status: {{ request('is_active') === '1' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        @endif

                        <a href="{{ route('admin.products.index') }}" class="product-filter-chip-clear">
                            Hapus Semua
                        </a>
                    </div>
                @endif
            </form>
        </section>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Produk</h2>

                    <p>
                        Total {{ number_format($products->total(), 0, ',', '.') }}
                        produk terdaftar.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $products->total() }} produk
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table product-crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Persediaan</th>
                            <th>Status</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $hasHistory = $product->hasTransactionHistory();

                                $grossProfit = max((float) $product->price - (float) $product->cost_price, 0);

                                $grossMargin = $product->price > 0 ? ($grossProfit / $product->price) * 100 : 0;

                                $isLowStock = $product->stock <= $product->minimum_stock;
                            @endphp

                            <tr>
                                <td>
                                    <div class="product-record-cell">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                                class="product-table-image" width="50" height="50">
                                        @else
                                            <span class="product-table-image-placeholder">
                                                {{ strtoupper(substr($product->name, 0, 1)) }}
                                            </span>
                                        @endif

                                        <div class="product-record-info">
                                            <strong>{{ $product->name }}</strong>

                                            <span>
                                                SKU {{ $product->sku }}
                                            </span>

                                            <small>
                                                Barcode:
                                                {{ $product->barcode ?: '-' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="crud-neutral-badge">
                                        {{ $product->category?->name ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="product-price-cell">
                                        <strong>
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </strong>

                                        <span>
                                            Modal:
                                            Rp {{ number_format($product->cost_price, 0, ',', '.') }}
                                        </span>

                                        <small>
                                            Margin {{ number_format($grossMargin, 1, ',', '.') }}%
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="product-stock-cell">
                                        <span
                                            class="product-stock-badge
                                            {{ $isLowStock ? 'is-danger' : 'is-success' }}">
                                            {{ number_format($product->stock, 0, ',', '.') }}
                                        </span>

                                        <small>
                                            Minimum {{ number_format($product->minimum_stock, 0, ',', '.') }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="crud-status
                                        {{ $product->is_active ? 'is-success' : 'is-muted' }}">
                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $product->updatedBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            Data:
                                            {{ $product->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>

                                        <small>
                                            Stok:
                                            {{ $product->last_stock_updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        @if (!$hasHistory)
                                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                                class="js-confirm-action" data-confirm-icon="warning"
                                                data-confirm-title="Hapus produk?"
                                                data-confirm-message="Produk {{ $product->name }} akan dipindahkan ke trash dan masih dapat dipulihkan."
                                                data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-light crud-locked-button"
                                                title="Produk memiliki riwayat transaksi" disabled>
                                                Terkunci
                                            </button>
                                        @endif
                                    </div>

                                    @if ($hasHistory)
                                        <small class="crud-lock-note">
                                            Memiliki riwayat transaksi
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            P
                                        </span>

                                        @if ($hasActiveFilters)
                                            <strong>Produk tidak ditemukan</strong>

                                            <p>
                                                Tidak ada produk yang cocok dengan kata kunci atau kriteria filter yang Anda terapkan.
                                            </p>

                                            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
                                                Reset Filter
                                            </a>
                                        @else
                                            <strong>Belum ada produk</strong>

                                            <p>
                                                Tambahkan produk pertama agar dapat digunakan
                                                pada transaksi penjualan.
                                            </p>

                                            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                                                Tambah Produk
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $products->firstItem() }}
                        sampai {{ $products->lastItem() }}
                        dari {{ $products->total() }} produk
                    </span>

                    {{ $products->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
