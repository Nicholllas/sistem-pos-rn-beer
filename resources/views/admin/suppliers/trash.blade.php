@extends('layouts.admin')

@section('title', 'Trash Supplier')
@section('page-title', 'Trash Supplier')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                Trash Supplier
            </h3>

            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary btn-sm">
                Kembali ke Supplier
            </a>
        </div>

        <div class="card-body">
            <div class="alert alert-info">
                Supplier yang ada di trash tidak tampil sebagai supplier aktif.
                Gunakan tombol restore jika ingin mengaktifkan kembali supplier.
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>Supplier</th>
                            <th>Kontak</th>
                            <th>Dihapus Pada</th>
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
                                        Alamat: {{ $supplier->address ?? '-' }}
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
                                    {{ $supplier->deleted_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    <form action="{{ route('admin.suppliers.restore', $supplier->id) }}" method="POST"
                                        onsubmit="return confirm('Restore supplier ini?')">
                                        @csrf
                                        @method('PUT')

                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Trash supplier kosong.
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
