@extends('layouts.admin')

@section('title', 'Log Stok')
@section('page-title', 'Log Stok')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Riwayat Pergerakan Stok</h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Tipe</th>
                            <th>Qty</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>User</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($stockMovements as $movement)
                            <tr>
                                <td>
                                    {{ $movement->created_at?->format('d M Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    <strong>{{ $movement->product?->name ?? '-' }}</strong>
                                    <small class="text-muted d-block">
                                        SKU: {{ $movement->product?->sku ?? '-' }}
                                    </small>
                                </td>

                                <td>
                                    {{ $movement->product?->category?->name ?? '-' }}
                                </td>

                                <td>
                                    @if ($movement->type === 'in')
                                        <span class="badge bg-success">IN</span>
                                    @elseif ($movement->type === 'out')
                                        <span class="badge bg-danger">OUT</span>
                                    @else
                                        <span class="badge bg-warning text-dark">ADJUSTMENT</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($movement->qty > 0)
                                        <span class="text-success">
                                            +{{ $movement->qty }}
                                        </span>
                                    @else
                                        <span class="text-danger">
                                            {{ $movement->qty }}
                                        </span>
                                    @endif
                                </td>

                                <td>{{ $movement->previous_stock }}</td>

                                <td>{{ $movement->new_stock }}</td>

                                <td>{{ $movement->createdBy?->name ?? '-' }}</td>

                                <td>{{ $movement->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Belum ada log stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($stockMovements->hasPages())
            <div class="card-footer">
                {{ $stockMovements->links() }}
            </div>
        @endif
    </div>

@endsection
