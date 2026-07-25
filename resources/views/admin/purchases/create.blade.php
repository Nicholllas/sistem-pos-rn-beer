@extends('layouts.admin')

@section('title', 'Pembelian Stok')
@section('page-title', 'Pembelian Stok')
@section('page-subtitle', 'Catat pembelian supplier, kurangi saldo kas, tambah stok, dan bentuk batch FIFO.')

@section('page-actions')
    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    @php
        $productsData = $products
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category?->name,
                    'cost_price' => (float) $product->cost_price,
                    'stock' => (int) $product->stock,
                ];
            })
            ->values();

        $oldItemsData = old('items', []);
    @endphp

    <div class="crud-page purchase-create-page">
        <form action="{{ route('admin.purchases.store') }}" method="POST" id="purchaseForm" class="purchase-form">
            @csrf

            <div class="purchase-create-layout">

                {{-- Informasi pembelian --}}
                <aside class="purchase-information-panel">
                    <div class="purchase-information-header">
                        <div>
                            <h2>Informasi Pembelian</h2>

                            <p>
                                Tentukan supplier, akun pembayaran,
                                dan tanggal penerimaan stok.
                            </p>
                        </div>
                    </div>

                    <div class="purchase-information-body">

                        {{-- Supplier --}}
                        <div class="purchase-form-group">
                            <label for="supplier_id" class="form-label">
                                Supplier
                                <span class="crud-required">*</span>
                            </label>

                            <select name="supplier_id" id="supplier_id"
                                class="form-select crud-form-control
                                    @error('supplier_id') is-invalid @enderror"
                                required>
                                <option value="">
                                    Pilih supplier
                                </option>

                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('supplier_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Supplier menjadi sumber transaksi pembelian.
                                </span>
                            </div>
                        </div>

                        {{-- Akun pembayaran --}}
                        <div class="purchase-form-group">
                            <label for="finance_account_id" class="form-label">
                                Akun Pembayaran
                                <span class="crud-required">*</span>
                            </label>

                            <select name="finance_account_id" id="finance_account_id"
                                class="form-select crud-form-control purchase-account-select
                                    @error('finance_account_id') is-invalid @enderror"
                                required>
                                <option value="">
                                    Pilih akun pembayaran
                                </option>

                                @foreach ($financeAccounts as $account)
                                    @php
                                        $accountFullLabel =
                                            $account->code .
                                            ' · ' .
                                            $account->name .
                                            ' · Saldo Rp ' .
                                            number_format($account->current_balance, 0, ',', '.');
                                    @endphp

                                    <option value="{{ $account->id }}" data-code="{{ $account->code }}"
                                        data-name="{{ $account->name }}"
                                        data-balance="{{ (float) $account->current_balance }}"
                                        data-full-label="{{ $accountFullLabel }}" @selected((string) old('finance_account_id') === (string) $account->id)>
                                        {{ $account->code }}
                                        ·
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('finance_account_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Total pembelian akan mengurangi saldo akun ini.
                                </span>
                            </div>

                            {{-- Preview saldo akun --}}
                            <div class="purchase-finance-reference" id="purchaseFinanceReference" hidden>
                                <div class="purchase-finance-reference-header">
                                    <div>
                                        <span>Akun dipilih</span>

                                        <strong id="purchaseFinanceAccountName">
                                            -
                                        </strong>
                                    </div>

                                    <span id="purchaseFinanceAccountCode">
                                        -
                                    </span>
                                </div>

                                <div class="purchase-finance-reference-grid">
                                    <div>
                                        <span>Saldo Saat Ini</span>

                                        <strong id="purchaseFinanceCurrentBalance">
                                            Rp 0
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Total Pembelian</span>

                                        <strong id="purchaseFinancePurchaseTotal">
                                            Rp 0
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Estimasi Saldo Setelah Pembelian</span>

                                        <strong id="purchaseFinanceRemainingBalance" class="is-neutral">
                                            Rp 0
                                        </strong>
                                    </div>
                                </div>

                                <div class="purchase-finance-balance-message is-neutral" id="purchaseFinanceBalanceMessage">
                                    Tambahkan produk untuk melihat estimasi saldo.
                                </div>
                            </div>
                        </div>

                        {{-- Tanggal pembelian --}}
                        <div class="purchase-form-group">
                            <label for="purchase_date" class="form-label">
                                Tanggal Pembelian
                                <span class="crud-required">*</span>
                            </label>

                            <input type="date" name="purchase_date" id="purchase_date"
                                class="form-control crud-form-control
                                    @error('purchase_date') is-invalid @enderror"
                                value="{{ old('purchase_date', now()->toDateString()) }}"
                                required>

                            @error('purchase_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Tanggal ini menjadi tanggal batch FIFO.
                                </span>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="purchase-form-group">
                            <label for="note" class="form-label">
                                Catatan
                            </label>

                            <textarea name="note" id="note" rows="4" maxlength="1000"
                                class="form-control crud-form-control
                                    @error('note') is-invalid @enderror"
                                placeholder="Nomor invoice supplier, pembelian mingguan, atau keterangan lainnya..."
                                data-character-counter="purchaseNoteCounter">{{ old('note') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Catatan tersimpan dalam histori pembelian.
                                </span>

                                <span id="purchaseNoteCounter">
                                    0/1000
                                </span>
                            </div>
                        </div>

                        {{-- Informasi proses --}}
                        <div class="purchase-process-info">
                            <strong>
                                Proses otomatis setelah disimpan
                            </strong>

                            <ul>
                                <li>Mengurangi saldo akun pembayaran.</li>
                                <li>Menambah stok produk.</li>
                                <li>Membuat batch FIFO baru.</li>
                                <li>Mencatat log pergerakan stok.</li>
                                <li>Menyimpan harga modal setiap batch.</li>
                            </ul>
                        </div>
                    </div>
                </aside>

                {{-- Detail produk --}}
                <section class="crud-panel purchase-items-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Detail Produk</h2>

                            <p>
                                Tambahkan produk, jumlah pembelian,
                                dan harga modal per unit.
                            </p>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" id="addPurchaseItemButton">
                            Tambah Produk
                        </button>
                    </div>

                    {{-- Error items --}}
                    @if (
                        $errors->has('items') ||
                            $errors->has('items.*.product_id') ||
                            $errors->has('items.*.qty') ||
                            $errors->has('items.*.cost_price'))
                        <div class="purchase-items-error">
                            <strong>
                                Detail produk belum valid.
                            </strong>

                            <ul>
                                @foreach ($errors->get('items') as $message)
                                    <li>{{ $message }}</li>
                                @endforeach

                                @foreach ($errors->get('items.*.product_id') as $messages)
                                    @foreach ($messages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                @endforeach

                                @foreach ($errors->get('items.*.qty') as $messages)
                                    @foreach ($messages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                @endforeach

                                @foreach ($errors->get('items.*.cost_price') as $messages)
                                    @foreach ($messages as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Ringkasan produk --}}
                    <div class="purchase-summary-strip">
                        <div>
                            <span>Jenis Produk</span>

                            <strong id="purchaseItemCount">
                                0
                            </strong>
                        </div>

                        <div>
                            <span>Total Qty</span>

                            <strong id="purchaseQtyTotal">
                                0
                            </strong>
                        </div>

                        <div>
                            <span>Total Pembelian</span>

                            <strong id="purchaseGrandTotalSummary">
                                Rp 0
                            </strong>
                        </div>
                    </div>

                    {{-- Grid produk tanpa horizontal slider --}}
                    <div class="purchase-items-grid" id="purchaseItemsTable">
                        <div class="purchase-items-grid-header">
                            <span>Produk</span>
                            <span>Qty</span>
                            <span>Harga Modal</span>
                            <span>Subtotal</span>
                            <span class="text-end">Aksi</span>
                        </div>

                        <div class="purchase-items-grid-body" id="purchaseItemsBody"></div>

                        <div class="purchase-items-grid-total">
                            <span>
                                Total Pembelian
                            </span>

                            <strong id="grandTotalText">
                                Rp 0
                            </strong>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="purchase-items-footer">
                        <div>
                            <strong>Harga modal batch FIFO</strong>

                            <p>
                                Harga modal setiap produk menjadi cost price
                                pada batch FIFO yang baru.
                            </p>
                        </div>

                        <div class="purchase-submit-actions">
                            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-success" id="purchaseSubmitButton">
                                Simpan Pembelian
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>

    {{-- Data produk untuk purchases.js --}}
    <script
        type="application/json"
        id="purchaseProductsData"
    >@json($productsData)</script>

    {{-- Mengembalikan item ketika validasi gagal --}}
    <script
        type="application/json"
        id="purchaseOldItemsData"
    >@json($oldItemsData)</script>
@endsection
