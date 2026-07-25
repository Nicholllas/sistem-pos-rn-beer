@extends('layouts.admin')

@section('title', 'Struk Penjualan')
@section('page-title', 'Struk Penjualan')
@section('content')
    @php
        $isCompleted = $sale->status === 'completed';

        $paymentMethod = strtolower((string) $sale->payment_method);
        $isCash = $paymentMethod === 'cash';
        $isSplit = $paymentMethod === 'split';

        $changeAmount = (float) $sale->change_amount;

        /*
         * Mengelompokkan nominal pembayaran berdasarkan metode.
         *
         * Contoh:
         * [
         *     'cash' => 50000,
         *     'qris' => 61000,
         * ]
         */
        $paymentAmounts = $sale->payments
            ->groupBy(fn($payment) => strtolower((string) $payment->payment_method))
            ->map(fn($payments) => (float) $payments->sum('amount'));

        $cashAmount = (float) $paymentAmounts->get('cash', 0);
        $hasCashPayment = $cashAmount > 0;

        $activeNonCashMethods = collect([
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
        ])
            ->filter(fn($label, $method) => (float) $paymentAmounts->get($method, 0) > 0)
            ->values()
            ->implode(' dan ');
    @endphp

    <div class="row receipt-layout">
        <div class="col-md-8 receipt-main-column">
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

        <div class="col-md-4 no-print receipt-action-column">
            <div class="card receipt-action-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="receipt-action-heading">
                        <h3 class="card-title">Aksi Transaksi</h3>

                        <small>
                            {{ $sale->invoice_number }}
                        </small>
                    </div>

                    <span class="badge {{ $isCompleted ? 'bg-success' : 'bg-danger' }}">
                        {{ $isCompleted ? 'Selesai' : 'Dibatalkan' }}
                    </span>
                </div>

                <div class="card-body">
                    {{-- Ringkasan Pembayaran --}}
                    <div class="payment-overview">
                        <div class="payment-overview-row">
                            <span>Metode Pembayaran</span>

                            <strong class="payment-method">
                                {{ strtoupper($sale->payment_method ?? '-') }}
                            </strong>
                        </div>

                        @if ($isSplit)
                            <div class="payment-breakdown">
                                <div class="payment-breakdown-title">
                                    Rincian Pembayaran
                                </div>

                                @foreach ([
            'cash' => 'Cash',
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
        ] as $method => $label)
                                    @php
                                        $methodAmount = (float) $paymentAmounts->get($method, 0);
                                    @endphp

                                    @if ($methodAmount > 0)
                                        <div class="payment-breakdown-row">
                                            <span>{{ $label }}</span>

                                            <strong>
                                                Rp {{ number_format($methodAmount, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="payment-overview-row">
                            <span>Total Belanja</span>

                            <strong>
                                Rp {{ number_format($sale->total, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="payment-overview-row">
                            <span>
                                {{ $isSplit ? 'Total Pembayaran' : 'Uang Diterima' }}
                            </span>

                            <strong>
                                Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}
                            </strong>
                        </div>

                        <div class="payment-overview-row payment-change-row">
                            <span>Kembalian</span>

                            <strong>
                                Rp {{ number_format($sale->change_amount, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>

                    {{-- Reminder Kasir --}}
                    @if ($isCompleted && ($isCash || ($isSplit && $hasCashPayment)) && $changeAmount > 0)
                        <div class="cashier-reminder cashier-reminder-warning" role="alert">
                            <div>
                                <strong>Jangan lupa berikan kembalian</strong>

                                <div class="cashier-reminder-amount">
                                    Rp {{ number_format($sale->change_amount, 0, ',', '.') }}
                                </div>

                                @if ($isSplit && $activeNonCashMethods !== '')
                                    <small class="mb-1">
                                        Pastikan pembayaran {{ $activeNonCashMethods }} sudah berhasil.
                                    </small>
                                @endif

                                <small>
                                    Pastikan jumlah kembalian sesuai sebelum pelanggan meninggalkan kasir.
                                </small>
                            </div>
                        </div>
                    @elseif ($isCompleted && $isSplit)
                        <div class="cashier-reminder cashier-reminder-info">
                            <div>
                                <strong>Pastikan seluruh pembayaran split berhasil</strong>

                                @if ($activeNonCashMethods !== '')
                                    <small>
                                        Periksa status pembayaran {{ $activeNonCashMethods }}.
                                    </small>
                                @endif

                                @if ($hasCashPayment)
                                    <small>
                                        Pastikan uang tunai yang diterima sudah sesuai.
                                    </small>
                                @endif
                            </div>
                        </div>
                    @elseif ($isCompleted && $isCash)
                        <div class="cashier-reminder cashier-reminder-success">
                            <div>
                                <strong>Uang diterima pas</strong>

                                <small>
                                    Transaksi ini tidak memiliki kembalian.
                                </small>
                            </div>
                        </div>
                    @elseif ($isCompleted)
                        <div class="cashier-reminder cashier-reminder-info">
                            <div>
                                <strong>Pastikan pembayaran sudah berhasil</strong>

                                <small>
                                    Periksa status pembayaran
                                    {{ strtoupper($sale->payment_method) }}
                                    sebelum menyelesaikan pelayanan pelanggan.
                                </small>
                            </div>
                        </div>
                    @endif

                    {{-- Aksi Utama --}}
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary w-100" onclick="window.print()">
                            Print Struk
                        </button>

                        <a href="{{ route('admin.sales.pos') }}" class="btn btn-success w-100"
                            @if ($isCompleted && $isCash && $changeAmount > 0) onclick="return confirm(
                            'Pastikan kembalian Rp {{ number_format($sale->change_amount, 0, ',', '.') }} sudah diberikan kepada pelanggan. Lanjut ke transaksi baru?'
                        )" @endif>
                            Transaksi Baru
                        </a>

                        <a href="{{ route('admin.sales.history') }}" class="btn btn-outline-secondary w-100">
                            Riwayat Penjualan
                        </a>
                    </div>

                    @if ($isCompleted)
                        {{-- Void dibuat secondary agar tidak mudah terpencet --}}
                        <details class="void-action" {{ $errors->has('void_reason') ? 'open' : '' }}>
                            <summary>
                                Batalkan Transaksi
                            </summary>

                            <div class="void-action-body">
                                <div class="void-warning">
                                    Gunakan hanya jika terjadi kesalahan transaksi.
                                    Stok produk akan dikembalikan secara otomatis.
                                </div>

                                <form action="{{ route('admin.sales.void', $sale) }}" method="POST"
                                    onsubmit="return confirm(
                                'Yakin ingin membatalkan transaksi {{ $sale->invoice_number }}? Stok produk akan dikembalikan.'
                            )">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label for="void_reason" class="form-label">
                                            Alasan Void
                                        </label>

                                        <textarea name="void_reason" id="void_reason" class="form-control @error('void_reason') is-invalid @enderror"
                                            rows="3" placeholder="Contoh: Salah input item atau salah metode pembayaran" required>{{ old('void_reason') }}</textarea>

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
                            </div>
                        </details>
                    @else
                        <div class="void-information">
                            <strong>Transaksi telah dibatalkan</strong>

                            <div class="void-information-row">
                                <span>Oleh</span>
                                <span>{{ $sale->voidedBy?->name ?? '-' }}</span>
                            </div>

                            <div class="void-information-row">
                                <span>Pada</span>
                                <span>
                                    {{ $sale->voided_at?->format('d M Y H:i') ?? '-' }}
                                </span>
                            </div>

                            <div class="void-information-reason">
                                <span>Alasan</span>
                                <p>{{ $sale->void_reason ?? '-' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
