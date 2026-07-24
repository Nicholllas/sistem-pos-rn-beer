@extends('layouts.admin')

@section('title', 'Kategori Terhapus')
@section('page-title', 'Kategori Terhapus')
@section('page-subtitle', 'Pulihkan kategori yang sebelumnya telah dihapus.')

@section('page-actions')
    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">
        Kembali ke Kategori
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="crud-notice">
            <span class="crud-notice-avatar">
                !
            </span>

            <div>
                <strong>Kategori belum terhapus permanen</strong>

                <p>
                    Kategori pada halaman ini masih dapat dipulihkan kembali.
                </p>
            </div>
        </div>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Kategori Terhapus</h2>

                    <p>
                        Total {{ number_format($categories->total(), 0, ',', '.') }}
                        kategori berada di trash.
                    </p>
                </div>

                <span class="crud-count-badge is-danger">
                    {{ $categories->total() }} terhapus
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Dibuat Oleh</th>
                            <th>Terakhir Diubah</th>
                            <th>Dihapus Pada</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <div class="crud-record-cell">
                                        <span class="crud-record-avatar is-danger">
                                            {{ strtoupper(mb_substr($category->name, 0, 1)) }}
                                        </span>

                                        <div>
                                            <strong>{{ $category->name }}</strong>

                                            <small>
                                                ID #{{ $category->id }}
                                            </small>
                                        </div>
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
                                    <span class="crud-date-badge is-danger">
                                        {{ $category->deleted_at?->format('d M Y H:i') ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <form action="{{ route('admin.categories.restore', $category->id) }}"
                                            method="POST" class="js-confirm-action" data-confirm-icon="question"
                                            data-confirm-title="Pulihkan kategori?"
                                            data-confirm-message="Kategori {{ $category->name }} akan dikembalikan ke daftar kategori aktif."
                                            data-confirm-button="Ya, Pulihkan" data-confirm-class="btn btn-success px-4">
                                            @csrf
                                            @method('PUT')

                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                Pulihkan
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar is-success">
                                            ✓
                                        </span>

                                        <strong>Trash kategori kosong</strong>

                                        <p>
                                            Tidak ada kategori yang sedang menunggu
                                            untuk dipulihkan.
                                        </p>

                                        <a href="{{ route('admin.categories.index') }}"
                                            class="btn btn-outline-primary btn-sm">
                                            Kembali ke Kategori
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
