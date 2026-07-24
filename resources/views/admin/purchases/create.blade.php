@extends('layouts.admin')

@section('title', 'Pembelian Stok')
@section('page-title', 'Pembelian Stok')
@section('page-subtitle', 'Catat pembelian supplier untuk menambah stok dan membuat batch FIFO.')

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

    <div class="crud-page">
        <form action="{{ route('admin.purchases.store') }}" method="POST" id="purchaseForm" class="purchase-form">
            @csrf

            <div class="purchase-create-layout">
                <aside class="purchase-information-panel">
                    <div class="purchase-information-header">
                        <span class="crud-form-avatar">
                            B
                        </span>

                        <div>
                            <h2>Informasi Pembelian</h2>

                            <p>
                                Tentukan supplier dan tanggal penerimaan stok.
                            </p>
                        </div>
                    </div>

                    <div class="purchase-information-body">
                        <div class="mb-3">
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
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('supplier_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="purchase_date" class="form-label">
                                Tanggal Pembelian
                                <span class="crud-required">*</span>
                            </label>

                            <input type="date" name="purchase_date" id="purchase_date"
                                class="form-control crud-form-control
                                    @error('purchase_date') is-invalid @enderror"
                                value="{{ old('purchase_date', now()->toDateString()) }}" required>

                            @error('purchase_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="crud-field-help">
                                <span>
                                    Tanggal ini digunakan sebagai tanggal batch FIFO.
                                </span>
                            </div>
                        </div>

                        <div>
                            <label for="note" class="form-label">
                                Catatan
                            </label>

                            <textarea name="note" id="note" rows="4"
                                class="form-control crud-form-control
                                    @error('note') is-invalid @enderror"
                                placeholder="Nomor invoice supplier, pembelian mingguan, atau keterangan lain...">{{ old('note') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="purchase-process-info">
                            <strong>Proses otomatis</strong>

                            <ul>
                                <li>Menambah stok produk.</li>
                                <li>Membuat batch FIFO baru.</li>
                                <li>Mencatat log pergerakan stok.</li>
                                <li>Menyimpan harga modal batch.</li>
                            </ul>
                        </div>
                    </div>
                </aside>

                <section class="crud-panel purchase-items-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Detail Produk</h2>

                            <p>
                                Tambahkan produk, jumlah beli, dan harga modal.
                            </p>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" id="addPurchaseItemButton">
                            Tambah Produk
                        </button>
                    </div>

                    @error('items')
                        <div class="purchase-items-error">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="purchase-summary-strip">
                        <div>
                            <span>Jenis Produk</span>
                            <strong id="purchaseItemCount">0</strong>
                        </div>

                        <div>
                            <span>Total Qty</span>
                            <strong id="purchaseQtyTotal">0</strong>
                        </div>

                        <div>
                            <span>Total Pembelian</span>
                            <strong id="purchaseGrandTotalSummary">Rp 0</strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table purchase-items-table align-middle mb-0" id="purchaseItemsTable">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th>Harga Modal</th>
                                    <th>Subtotal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="purchaseItemsBody"></tbody>

                            <tfoot>
                                <tr>
                                    <th colspan="3">
                                        Total Pembelian
                                    </th>

                                    <th id="grandTotalText">
                                        Rp 0
                                    </th>

                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="purchase-items-footer">
                        <div>
                            <strong>Harga modal batch FIFO</strong>

                            <p>
                                Harga modal setiap baris akan menjadi cost price
                                batch FIFO baru.
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

    <script
        type="application/json"
        id="purchaseProductsData"
    >@json($productsData)</script>

    <script
        type="application/json"
        id="purchaseOldItemsData"
    >@json($oldItemsData)</script>
@endsection
