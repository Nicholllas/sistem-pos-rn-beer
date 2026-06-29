@extends('layouts.admin')

@section('title', 'Riwayat Penjualan')
@section('page-title', 'Riwayat Penjualan')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Riwayat Penjualan
            </h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th>Total</th>
                            <th>Bayar</th>
                            <th>Kembalian</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sales.receipt', $sale) }}" class="fw-bold text-decoration-none">
                                        {{ $sale->invoice_number }}
                                    </a>
                                </td>

                                <td>
                                    {{ $sale->soldBy?->name ?? '-' }}
                                </td>

                                <td>
                                    Rp {{ number_format($sale->total, 0, ',', '.') }}
                                </td>

                                <td>
                                    Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}
                                </td>

                                <td>
                                    Rp {{ number_format($sale->change_amount, 0, ',', '.') }}
                                </td>

                                <td>
                                    {{ strtoupper($sale->payment_method) }}
                                </td>

                                <td>
                                    @if ($sale->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($sale->status) }}</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $sale->created_at?->format('d M Y H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada transaksi penjualan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($sales->hasPages())
            <div class="card-footer">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

@endsection
