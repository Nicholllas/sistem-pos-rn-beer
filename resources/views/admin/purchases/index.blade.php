@extends('layouts.admin')

@section('title', 'Riwayat Pembelian')
@section('page-title', 'Riwayat Pembelian')
@section('page-subtitle', 'Pantau pembelian stok, supplier, nilai pembelian, dan status transaksi.')

@section('page-actions')
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
        Pembelian Stok Baru
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Riwayat Pembelian Stok</h2>

                    <p>
                        Total {{ number_format($purchases->total(), 0, ',', '.') }}
                        transaksi pembelian tercatat.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $purchases->total() }} pembelian
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table purchase-history-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pembelian</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Diinput Oleh</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($purchases as $purchase)
                            @php
                                $isVoid = $purchase->status === 'void';
                                $supplierName = $purchase->supplier?->name ?? 'Supplier tidak ditemukan';
                            @endphp

                            <tr @class(['is-void' => $isVoid])>
                                <td>
                                    <div class="purchase-number-cell">
                                        <a href="{{ route('admin.purchases.show', $purchase) }}">
                                            {{ $purchase->purchase_number }}
                                        </a>

                                        <span>
                                            {{ $purchase->purchase_date?->format('d M Y') ?? '-' }}
                                        </span>

                                        <small>
                                            Dibuat
                                            {{ $purchase->created_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-supplier-cell">
                                        <span class="purchase-supplier-avatar">
                                            {{ strtoupper(mb_substr($supplierName, 0, 1)) }}
                                        </span>

                                        <div>
                                            <strong>{{ $supplierName }}</strong>
                                            <small>Supplier pembelian</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="purchase-total-cell">
                                        <strong>
                                            Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                        </strong>

                                        <small>
                                            {{ $isVoid ? 'Nilai dibatalkan' : 'Total pembelian' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    @if ($purchase->status === 'completed')
                                        <span class="crud-status is-success">
                                            Selesai
                                        </span>
                                    @else
                                        <span class="crud-status is-danger">
                                            Void
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $purchase->createdBy?->name ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $purchase->created_at?->format('d M Y H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.purchases.show', $purchase) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            B
                                        </span>

                                        <strong>Belum ada pembelian stok</strong>

                                        <p>
                                            Catat pembelian pertama untuk menambah stok,
                                            membuat batch FIFO, dan mencatat harga modal.
                                        </p>

                                        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">
                                            Buat Pembelian
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($purchases->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $purchases->firstItem() }}
                        sampai {{ $purchases->lastItem() }}
                        dari {{ $purchases->total() }} pembelian
                    </span>

                    {{ $purchases->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
