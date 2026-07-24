@extends('layouts.admin')

@section('title', 'Supplier')
@section('page-title', 'Supplier')
@section('page-subtitle', 'Kelola data supplier yang digunakan dalam transaksi pembelian stok.')

@section('page-actions')
    <a href="{{ route('admin.suppliers.trash') }}" class="btn btn-outline-secondary">
        Supplier Terhapus
    </a>

    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
        Tambah Supplier
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Supplier</h2>

                    <p>
                        Total {{ number_format($suppliers->total(), 0, ',', '.') }}
                        supplier terdaftar.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $suppliers->total() }} supplier
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Kontak</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($suppliers as $supplier)
                            @php
                                $hasHistory = $supplier->hasTransactionHistory();
                            @endphp

                            <tr>
                                <td>
                                    <div class="crud-record-cell">
                                        <span class="crud-record-avatar">
                                            {{ strtoupper(mb_substr($supplier->name, 0, 1)) }}
                                        </span>

                                        <div>
                                            <strong>{{ $supplier->name }}</strong>

                                            <small>
                                                ID #{{ $supplier->id }}
                                            </small>

                                            <small>
                                                Dibuat oleh:
                                                {{ $supplier->createdBy?->name ?? '-' }}
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
                                    <span
                                        class="crud-status
                                        {{ $supplier->is_active ? 'is-success' : 'is-muted' }}">
                                        {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $supplier->updatedBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $supplier->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.suppliers.edit', $supplier) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        @if (!$hasHistory)
                                            <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST"
                                                class="js-confirm-action" data-confirm-icon="warning"
                                                data-confirm-title="Hapus supplier?"
                                                data-confirm-message="Supplier {{ $supplier->name }} akan dipindahkan ke trash dan masih dapat dipulihkan."
                                                data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-light crud-locked-button"
                                                title="Supplier memiliki riwayat transaksi pembelian" disabled>
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
                                <td colspan="6">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            S
                                        </span>

                                        <strong>Belum ada supplier</strong>

                                        <p>
                                            Tambahkan supplier pertama agar dapat digunakan
                                            saat mencatat pembelian stok.
                                        </p>

                                        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
                                            Tambah Supplier
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
