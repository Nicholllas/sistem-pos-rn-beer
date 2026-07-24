@extends('layouts.admin')

@section('title', 'Supplier Terhapus')
@section('page-title', 'Supplier Terhapus')
@section('page-subtitle', 'Pulihkan supplier yang sebelumnya telah dihapus.')

@section('page-actions')
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
        Kembali ke Supplier
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="crud-notice">
            <span class="crud-notice-avatar">
                !
            </span>

            <div>
                <strong>Supplier belum terhapus permanen</strong>

                <p>
                    Supplier pada halaman ini tidak muncul dalam daftar aktif,
                    tetapi masih dapat dipulihkan.
                </p>
            </div>
        </div>

        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Supplier Terhapus</h2>

                    <p>
                        Total {{ number_format($suppliers->total(), 0, ',', '.') }}
                        supplier berada di trash.
                    </p>
                </div>

                <span class="crud-count-badge is-danger">
                    {{ $suppliers->total() }} terhapus
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Kontak</th>
                            <th>Alamat</th>
                            <th>Dihapus Pada</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td>
                                    <div class="crud-record-cell">
                                        <span class="crud-record-avatar is-danger">
                                            {{ strtoupper(mb_substr($supplier->name, 0, 1)) }}
                                        </span>

                                        <div>
                                            <strong>{{ $supplier->name }}</strong>

                                            <small>
                                                ID #{{ $supplier->id }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $supplier->phone ?: '-' }}
                                        </strong>

                                        <small>
                                            {{ $supplier->email ?: 'Email belum tersedia' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-text-wrap">
                                        {{ $supplier->address ?: '-' }}
                                    </div>
                                </td>

                                <td>
                                    <span class="crud-date-badge is-danger">
                                        {{ $supplier->deleted_at?->format('d M Y H:i') ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <form action="{{ route('admin.suppliers.restore', $supplier->id) }}" method="POST"
                                            class="js-confirm-action" data-confirm-icon="question"
                                            data-confirm-title="Pulihkan supplier?"
                                            data-confirm-message="Supplier {{ $supplier->name }} akan dikembalikan ke daftar supplier aktif."
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

                                        <strong>Trash supplier kosong</strong>

                                        <p>
                                            Tidak ada supplier yang sedang menunggu
                                            untuk dipulihkan.
                                        </p>

                                        <a href="{{ route('admin.suppliers.index') }}"
                                            class="btn btn-outline-primary btn-sm">
                                            Kembali ke Supplier
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($suppliers->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $suppliers->firstItem() }}
                        sampai {{ $suppliers->lastItem() }}
                        dari {{ $suppliers->total() }} supplier
                    </span>

                    {{ $suppliers->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
