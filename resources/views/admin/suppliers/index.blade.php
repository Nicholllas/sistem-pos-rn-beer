@extends('layouts.admin')

@section('title', 'Supplier')
@section('page-title', 'Supplier')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                Daftar Supplier
            </h3>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.suppliers.trash') }}" class="btn btn-secondary btn-sm">
                    Trash
                </a>

                <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary btn-sm">
                    + Tambah Supplier
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>Supplier</th>
                            <th>Kontak</th>
                            <th>Alamat</th>
                            <th class="text-center">Status</th>
                            <th>Update Terakhir</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td class="text-center">
                                    {{ $suppliers->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <strong>{{ $supplier->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        Dibuat oleh: {{ $supplier->createdBy?->name ?? '-' }}
                                    </small>
                                </td>

                                <td>
                                    <div>
                                        <strong>HP:</strong>
                                        {{ $supplier->phone ?? '-' }}
                                    </div>

                                    <div>
                                        <strong>Email:</strong>
                                        {{ $supplier->email ?? '-' }}
                                    </div>
                                </td>

                                <td>
                                    {{ $supplier->address ?? '-' }}
                                </td>

                                <td class="text-center">
                                    @if ($supplier->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger">Nonaktif</span>
                                    @endif
                                </td>

                                <td>
                                    <small>
                                        {{ $supplier->updatedBy?->name ?? '-' }}
                                        <br>
                                        {{ $supplier->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                    </small>
                                </td>

                                <td>
                                    <div class="d-grid gap-1">
                                        <a href="{{ route('admin.suppliers.edit', $supplier) }}"
                                            class="btn btn-warning btn-sm">
                                            Edit
                                        </a>

                                        @if (!$supplier->hasTransactionHistory())
                                            <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Yakin hapus supplier ini?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-danger btn-sm" disabled>
                                                Hapus
                                            </button>

                                            <small class="text-muted d-block" style="line-height: 1.15;">
                                                Tidak bisa dihapus karena sudah memiliki riwayat transaksi.
                                            </small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada supplier.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($suppliers->hasPages())
            <div class="card-footer">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

@endsection
