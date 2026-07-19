@extends('layouts.admin')

@section('title', 'Struk Penjualan')
@section('page-title', 'Struk Penjualan')

@push('styles')
    @vite(['resources/css/receipt.css'])
@endpush

@section('content')

    <div class="row">
        <div class="col-md-8">
            <div class="d-flex justify-content-center">
                <div class="thermal-receipt" id="receiptArea">
                    <div class="receipt-header">
                        <h4>RN BEER</h4>
                        <p>Struk Penjualan</p>
                    </div>

                    @if ($sale->status === 'void')
                        <div class="receipt-line"></div>

                        <div class="text-center">
                            <strong>*** VOID ***</strong>
                        </div>

                        <div class="receipt-line"></div>
                    @endif

                    <div class="receipt-info">
                        <div>
                            <span>Invoice</span>
                            <span>{{ $sale->invoice_number }}</span>
                        </div>

                        <div>
                            <span>Tanggal</span>
                            <span>{{ $sale->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </div>

                        <div>
                            <span>Kasir</span>
                            <span>{{ $sale->soldBy?->name ?? '-' }}</span>
                        </div>

                        <div>
                            <span>Bayar</span>
                            <span>{{ strtoupper($sale->payment_method) }}</span>
                        </div>

                        <div>
                            <span>Status</span>
                            <span>
                                @if ($sale->status === 'completed')
                                    COMPLETED
                                @else
                                    VOID
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="receipt-line"></div>

                    <div class="receipt-items">
                        @foreach ($sale->items as $item)
                            <div class="receipt-item">
                                <div class="item-name">
                                    {{ $item->product?->name ?? '-' }}
                                </div>

                                <div class="item-detail">
                                    <span>
                                        {{ $item->qty }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                                    </span>

                                    <span>
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="receipt-line"></div>

                    <div class="receipt-summary">
                        <div>
                            <span>Total</span>
                            <strong>Rp {{ number_format($sale->total, 0, ',', '.') }}</strong>
                        </div>

                        <div>
                            <span>Bayar</span>
                            <span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
                        </div>

                        <div>
                            <span>Kembali</span>
                            <span>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($sale->status === 'void')
                        <div class="receipt-line"></div>

                        <div class="receipt-info">
                            <div>
                                <span>Void Oleh</span>
                                <span>{{ $sale->voidedBy?->name ?? '-' }}</span>
                            </div>

                            <div>
                                <span>Void Pada</span>
                                <span>{{ $sale->voided_at?->format('d/m/Y H:i') ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="mt-1">
                            <strong>Alasan Void:</strong>
                            <br>
                            {{ $sale->void_reason ?? '-' }}
                        </div>
                    @endif

                    <div class="receipt-line"></div>

                    <div class="receipt-footer">
                        @if ($sale->status === 'completed')
                            <p>Terima kasih sudah berbelanja.</p>
                            <p>Barang yang sudah dibeli tidak dapat dikembalikan.</p>
                        @else
                            <p>Struk ini adalah bukti transaksi yang sudah dibatalkan.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 no-print">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Aksi</h3>
                </div>

                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        Print Struk
                    </button>

                    @if ($sale->status === 'completed')
                        <hr>

                        <form action="{{ route('admin.sales.void', $sale) }}" method="POST"
                            onsubmit="return confirm('Yakin ingin membatalkan transaksi ini? Stok akan dikembalikan.')">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="void_reason" class="form-label">
                                    Alasan Void
                                </label>

                                <textarea name="void_reason" id="void_reason" class="form-control @error('void_reason') is-invalid @enderror"
                                    rows="3" placeholder="Contoh: Salah input item / salah metode bayar" required>{{ old('void_reason') }}</textarea>

                                @error('void_reason')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-danger w-100">
                                Void / Batalkan Transaksi
                            </button>
                        </form>
                    @else
                        <hr>

                        <div class="alert alert-danger mb-0">
                            <strong>Transaksi sudah dibatalkan.</strong>

                            <br>

                            <small>
                                Oleh: {{ $sale->voidedBy?->name ?? '-' }}
                            </small>

                            <br>

                            <small>
                                Pada: {{ $sale->voided_at?->format('d M Y H:i') ?? '-' }}
                            </small>

                            <br>

                            <small>
                                Alasan: {{ $sale->void_reason ?? '-' }}
                            </small>
                        </div>
                    @endif

                    <a href="{{ route('admin.sales.pos') }}" class="btn btn-success">
                        Transaksi Baru
                    </a>

                    <a href="{{ route('admin.sales.history') }}" class="btn btn-secondary">
                        Riwayat Penjualan
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
