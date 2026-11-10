@extends('layouts.admin')

@section('title', 'Kategori')
@section('page-title', 'Kategori Produk')
@section('page-subtitle', 'Kelola kategori yang digunakan untuk mengelompokkan produk.')

@section('page-actions')
    <a href="{{ route('admin.categories.trash') }}" class="btn btn-outline-secondary">
        Kategori Terhapus
    </a>

    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
        Tambah Kategori
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Kategori</h2>

                    <p>
                        Total {{ number_format($categories->total(), 0, ',', '.') }}
                        kategori terdaftar.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $categories->total() }} kategori
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Jumlah Produk</th>
                            <th>Dibuat Oleh</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                            @php
                                $productsCount = (int) ($category->products_count ?? 0);
                            @endphp

                            <tr>
                                <td>
                                    <div class="crud-record-cell">
                                        <div>
                                            <strong>{{ $category->name }}</strong>

                                            <small>
                                                ID #{{ $category->id }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-count-value">
                                        <strong>{{ $productsCount }}</strong>
                                        <span>produk</span>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $category->createdBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $category->created_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $category->updatedBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $category->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.categories.edit', $category) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        @if ($productsCount <= 0)
                                            <form action="{{ route('admin.categories.destroy', $category) }}"
                                                method="POST" class="js-confirm-action" data-confirm-icon="warning"
                                                data-confirm-title="Hapus kategori?"
                                                data-confirm-message="Kategori {{ $category->name }} akan dipindahkan ke trash dan masih dapat dipulihkan."
                                                data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-light crud-locked-button"
                                                title="Kategori masih digunakan oleh produk" disabled>
                                                Terkunci
                                            </button>
                                        @endif
                                    </div>

                                    @if ($productsCount > 0)
                                        <small class="crud-lock-note">
                                            Digunakan oleh {{ $productsCount }} produk
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            K
                                        </span>

                                        <strong>Belum ada kategori</strong>

                                        <p>
                                            Tambahkan kategori pertama untuk mulai
                                            mengelompokkan produk.
                                        </p>

                                        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                                            Tambah Kategori
                                        </a>
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
