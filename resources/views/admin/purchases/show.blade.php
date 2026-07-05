@extends('layouts.admin')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail Pembelian')

@section('content')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">
                        Detail Pembelian
                    </h3>

                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-sm">
                            Kembali
                        </a>

                        @if ($purchase->status === 'completed')
                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#voidPurchaseModal">
                                Void Pembelian
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if ($purchase->status === 'void')
                        <div class="alert alert-danger">
                            <strong>VOID</strong>
                            <br>
                            Pembelian ini sudah dibatalkan.
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th style="width: 160px;">No. Pembelian</th>
                                    <td>: {{ $purchase->purchase_number }}</td>
                                </tr>

                                <tr>
                                    <th>Supplier</th>
                                    <td>: {{ $purchase->supplier?->name ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th>Tanggal</th>
                                    <td>: {{ $purchase->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th>Status</th>
                                    <td>
                                        :
                                        @if ($purchase->status === 'completed')
                                            <span class="badge bg-success">COMPLETED</span>
                                        @else
                                            <span class="badge bg-danger">VOID</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th style="width: 160px;">Diinput Oleh</th>
                                    <td>: {{ $purchase->createdBy?->name ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th>Diinput Pada</th>
                                    <td>: {{ $purchase->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th>Catatan</th>
                                    <td>: {{ $purchase->note ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th>Total</th>
                                    <td>
                                        :
                                        <strong>
                                            Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                        </strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if ($purchase->status === 'void')
                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Void Oleh</strong>
                                <br>
                                {{ $purchase->voidedBy?->name ?? '-' }}
                            </div>

                            <div class="col-md-6">
                                <strong>Void Pada</strong>
                                <br>
                                {{ $purchase->voided_at?->format('d/m/Y H:i') ?? '-' }}
                            </div>

                            <div class="col-12 mt-2">
                                <strong>Alasan Void</strong>
                                <br>
                                {{ $purchase->void_reason ?? '-' }}
                            </div>
                        </div>
                    @endif

                    <hr>

                    <h5 class="mb-3">
                        Item Pembelian
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">No</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga Modal</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($purchase->items as $item)
                                    <tr>
                                        <td class="text-center">
                                            {{ $loop->iteration }}
                                        </td>

                                        <td>
                                            <strong>{{ $item->product?->name ?? '-' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                SKU: {{ $item->product?->sku ?? '-' }}
                                            </small>
                                        </td>

                                        <td>
                                            {{ $item->product?->category?->name ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            {{ $item->qty }}
                                        </td>

                                        <td class="text-end text-nowrap">
                                            Rp {{ number_format($item->cost_price, 0, ',', '.') }}
                                        </td>

                                        <td class="text-end text-nowrap">
                                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot>
                                <tr class="fw-bold">
                                    <th colspan="5" class="text-end">
                                        TOTAL
                                    </th>

                                    <th class="text-end text-nowrap">
                                        Rp {{ number_format($purchase->total, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        Informasi FIFO
                    </h3>
                </div>

                <div class="card-body">
                    <p class="mb-2">
                        Setiap item pembelian membuat batch FIFO baru.
                    </p>

                    <ul class="mb-0">
                        <li>Batch dipakai saat penjualan.</li>
                        <li>Harga modal transaksi mengikuti batch.</li>
                        <li>Pembelian hanya bisa di-void jika batch belum terjual.</li>
                    </ul>
                </div>
            </div>

            @if ($purchase->status === 'completed')
                <div class="alert alert-warning">
                    Void pembelian akan mengurangi stok kembali.
                    Jika batch sudah pernah terjual, void akan ditolak sistem.
                </div>
            @endif
        </div>
    </div>

    @if ($purchase->status === 'completed')
        <div class="modal fade" id="voidPurchaseModal" tabindex="-1" aria-labelledby="voidPurchaseModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('admin.purchases.void', $purchase) }}" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="voidPurchaseModalLabel">
                            Void Pembelian
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="alert alert-danger">
                            Yakin ingin membatalkan pembelian ini?
                            Stok dari pembelian ini akan dikurangi kembali.
                        </div>

                        <div class="mb-3">
                            <label for="void_reason" class="form-label">
                                Alasan Void <span class="text-danger">*</span>
                            </label>

                            <textarea name="void_reason" id="void_reason" rows="4"
                                class="form-control @error('void_reason') is-invalid @enderror"
                                placeholder="Contoh: Salah input qty / salah supplier / salah harga modal..." required>{{ old('void_reason') }}</textarea>

                            @error('void_reason')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Pembelian tidak bisa dibatalkan jika batch dari pembelian ini sudah terjual.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-danger">
                            Ya, Void Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    @if ($errors->has('void_reason'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalElement = document.getElementById('voidPurchaseModal');

                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        </script>
    @endif
@endpush
