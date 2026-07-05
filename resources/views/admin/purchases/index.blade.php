@extends('layouts.admin')

@section('title', 'Riwayat Pembelian')
@section('page-title', 'Riwayat Pembelian')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                Riwayat Pembelian Stok
            </h3>

            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">
                + Pembelian Stok
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>No. Pembelian</th>
                            <th>Supplier</th>
                            <th>Tanggal</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                            <th>Diinput Oleh</th>
                            <th style="width: 120px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td class="text-center">
                                    {{ $purchases->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <strong>{{ $purchase->purchase_number }}</strong>
                                </td>

                                <td>
                                    {{ $purchase->supplier?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}
                                </td>

                                <td class="text-end text-nowrap">
                                    Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    @if ($purchase->status === 'completed')
                                        <span class="badge bg-success">COMPLETED</span>
                                    @else
                                        <span class="badge bg-danger">VOID</span>
                                    @endif
                                </td>

                                <td>
                                    <small>
                                        {{ $purchase->createdBy?->name ?? '-' }}
                                        <br>
                                        {{ $purchase->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </small>
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-info btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada data pembelian stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($purchases->hasPages())
            <div class="card-footer">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

@endsection
