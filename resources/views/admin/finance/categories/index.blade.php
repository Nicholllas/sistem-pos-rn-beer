@extends('layouts.admin')

@section('title', 'Kategori Finance')
@section('page-title', 'Kategori Finance')

@section('content')

    @include('admin.finance.partials.alerts')

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Filter Kategori</h3>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.finance.categories.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="search" class="form-label">
                            Pencarian
                        </label>

                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ request('search') }}" placeholder="Cari kode atau nama kategori">
                    </div>

                    <div class="col-md-3">
                        <label for="type" class="form-label">
                            Jenis
                        </label>

                        <select name="type" id="type" class="form-select">
                            <option value="">Semua jenis</option>
                            <option value="income" @selected(request('type') === 'income')>
                                Pemasukan
                            </option>
                            <option value="expense" @selected(request('type') === 'expense')>
                                Pengeluaran
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select name="status" id="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="active" @selected(request('status') === 'active')>
                                Aktif
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')>
                                Nonaktif
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Filter
                        </button>

                        <a href="{{ route('admin.finance.categories.index') }}" class="btn btn-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                Daftar Kategori Finance
            </h3>

            <a href="{{ route('admin.finance.categories.create') }}" class="btn btn-primary btn-sm">
                Tambah Kategori
            </a>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Kategori</th>
                            <th>Jenis</th>
                            <th class="text-center">Sumber</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-center">Status</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <span class="fw-semibold">
                                        {{ $category->code }}
                                    </span>
                                </td>

                                <td>{{ $category->name }}</td>

                                <td>
                                    @if ($category->type === 'income')
                                        <span class="badge bg-success">
                                            Pemasukan
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            Pengeluaran
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if ($category->is_system)
                                        <span class="badge bg-primary">
                                            Sistem
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Manual
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ number_format($category->transactions_count) }}
                                </td>

                                <td class="text-center">
                                    @if ($category->is_active)
                                        <span class="badge bg-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.finance.categories.edit', $category) }}"
                                            class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.finance.categories.destroy', $category) }}"
                                            method="POST" onsubmit="return confirm('Hapus kategori finance ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm"
                                                @disabled($category->is_system)>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Belum ada kategori finance.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($categories->hasPages())
            <div class="card-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

@endsection
