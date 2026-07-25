@extends('layouts.admin')

@section('title', 'Kategori Finance')
@section('page-title', 'Kategori Finance')
@section('page-subtitle', 'Kelola kategori pemasukan dan pengeluaran untuk pencatatan transaksi.')

@section('page-actions')
    <a href="{{ route('admin.finance.categories.create') }}" class="btn btn-primary">
        Tambah Kategori
    </a>
@endsection

@section('content')
    @include('admin.finance.partials.alerts')

    @php
        $filterKeys = ['search', 'type', 'status'];
        $activeFilterCount = collect($filterKeys)->filter(fn($key) => request()->filled($key))->count();
        $hasActiveFilters = $activeFilterCount > 0;

        $categoryTypeLabels = [
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
        ];

        $categoryStatusLabels = [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ];
    @endphp

    <div class="crud-page finance-page">
        <section class="crud-panel finance-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Kategori</h2>

                    <p>
                        Cari kategori berdasarkan kode, nama, jenis, atau statusnya.
                    </p>
                </div>

                @if ($hasActiveFilters)
                    <span class="crud-count-badge">
                        {{ $activeFilterCount }} filter aktif
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.finance.categories.index') }}" method="GET">
                <div class="finance-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">
                                Pencarian
                            </label>

                            <input type="search" name="search" id="search" class="form-control crud-form-control"
                                value="{{ request('search') }}" placeholder="Cari kode atau nama kategori..."
                                autocomplete="off">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="type" class="form-label">
                                Jenis
                            </label>

                            <select name="type" id="type" class="form-select crud-form-control">
                                <option value="">Semua jenis</option>
                                <option value="income" @selected(request('type') === 'income')>Pemasukan</option>
                                <option value="expense" @selected(request('type') === 'expense')>Pengeluaran</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="status" class="form-label">
                                Status
                            </label>

                            <select name="status" id="status" class="form-select crud-form-control">
                                <option value="">Semua status</option>
                                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-3 finance-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>

                            @if ($hasActiveFilters)
                                <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-outline-secondary">
                                    Hapus Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="finance-active-filters">
                    <span class="finance-active-filter-label">
                        Filter:
                    </span>

                    @if (request()->filled('search'))
                        <span class="finance-filter-chip">
                            Pencarian: {{ request('search') }}
                        </span>
                    @endif

                    @if (request()->filled('type'))
                        <span class="finance-filter-chip">
                            Jenis: {{ $categoryTypeLabels[request('type')] ?? request('type') }}
                        </span>
                    @endif

                    @if (request()->filled('status'))
                        <span class="finance-filter-chip">
                            Status: {{ $categoryStatusLabels[request('status')] ?? request('status') }}
                        </span>
                    @endif
                </div>
            @endif
        </section>

        <section class="crud-panel finance-result-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Kategori Finance</h2>

                    <p>
                        @if ($hasActiveFilters)
                            Kategori yang sesuai dengan filter yang diterapkan.
                        @else
                            Seluruh kategori pemasukan dan pengeluaran yang tersedia.
                        @endif
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ number_format($categories->total(), 0, ',', '.') }}
                    kategori
                </span>
            </div>

            <div class="finance-result-summary">
                <span>
                    Menampilkan
                    <strong>{{ $categories->firstItem() ?? 0 }}</strong>
                    sampai
                    <strong>{{ $categories->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $categories->total() }}</strong>
                    kategori
                </span>

                @if ($categories->hasPages())
                    <span>
                        Halaman {{ $categories->currentPage() }}
                        dari {{ $categories->lastPage() }}
                    </span>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table crud-table finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th>Sumber</th>
                            <th>Transaksi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <div class="crud-record-cell">
                                        <div>
                                            <strong>{{ $category->name }}</strong>
                                            <small class="finance-record-code">{{ $category->code }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="finance-type-badge {{ $category->type === 'income' ? 'is-income' : 'is-expense' }}">
                                        {{ $category->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </td>

                                <td>
                                    @if ($category->is_system)
                                        <span class="crud-status is-warning">
                                            Sistem
                                        </span>
                                    @else
                                        <span class="crud-neutral-badge">
                                            Manual
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="crud-count-value">
                                        <strong>
                                            {{ number_format($category->transactions_count, 0, ',', '.') }}
                                        </strong>
                                        <span>transaksi</span>
                                    </div>
                                </td>

                                <td>
                                    <span @class([
                                        'crud-status',
                                        'is-success' => $category->is_active,
                                        'is-muted' => !$category->is_active,
                                    ])>
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.finance.categories.edit', $category) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        @if ($category->is_system)
                                            <button type="button" class="btn btn-sm btn-light crud-locked-button"
                                                title="Kategori sistem tidak dapat dihapus" disabled>
                                                Terkunci
                                            </button>
                                        @else
                                            <form action="{{ route('admin.finance.categories.destroy', $category) }}"
                                                method="POST" class="js-confirm-action" data-confirm-icon="warning"
                                                data-confirm-title="Hapus kategori?"
                                                data-confirm-message="Kategori {{ $category->name }} akan dihapus jika belum digunakan dalam transaksi."
                                                data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if ($category->is_system)
                                        <small class="crud-lock-note">
                                            Dikelola sistem
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="crud-empty-state">
                                        @if ($hasActiveFilters)
                                            <strong>Kategori tidak ditemukan</strong>

                                            <p>
                                                Tidak ada kategori yang sesuai dengan kombinasi filter tersebut.
                                            </p>

                                            <a href="{{ route('admin.finance.categories.index') }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Hapus Filter
                                            </a>
                                        @else
                                            <strong>Belum ada kategori finance</strong>

                                            <p>
                                                Tambahkan kategori untuk mengelompokkan pemasukan dan pengeluaran.
                                            </p>

                                            <a href="{{ route('admin.finance.categories.create') }}"
                                                class="btn btn-primary btn-sm">
                                                Tambah Kategori
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $categories->firstItem() }}
                        sampai {{ $categories->lastItem() }}
                        dari {{ $categories->total() }} kategori
                    </span>

                    {{ $categories->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
