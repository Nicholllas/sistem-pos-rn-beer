@extends('layouts.admin')

@section('title', 'Pembelian Stok')
@section('page-title', 'Pembelian Stok')

@section('content')

    <form action="{{ route('admin.purchases.store') }}" method="POST" id="purchaseForm">
        @csrf

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            Informasi Pembelian
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">
                                Supplier <span class="text-danger">*</span>
                            </label>

                            <select name="supplier_id" id="supplier_id"
                                class="form-select @error('supplier_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Supplier --</option>

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
                                Tanggal Pembelian <span class="text-danger">*</span>
                            </label>

                            <input type="date" name="purchase_date" id="purchase_date"
                                class="form-control @error('purchase_date') is-invalid @enderror"
                                value="{{ old('purchase_date', now()->toDateString()) }}" required>

                            @error('purchase_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="text-muted">
                                Tanggal ini akan menjadi tanggal batch FIFO.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">
                                Catatan
                            </label>

                            <textarea name="note" id="note" rows="4" class="form-control @error('note') is-invalid @enderror"
                                placeholder="Contoh: Pembelian mingguan / invoice supplier...">{{ old('note') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="alert alert-info mb-0">
                            Pembelian ini akan otomatis menambah stok, membuat batch FIFO,
                            dan mencatat log stok.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            Detail Produk
                        </h3>

                        <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                            + Tambah Produk
                        </button>
                    </div>

                    <div class="card-body">
                        @error('items')
                            <div class="alert alert-danger">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="purchaseItemsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 240px;">Produk</th>
                                        <th style="width: 110px;" class="text-center">Qty</th>
                                        <th style="width: 180px;" class="text-end">Harga Modal</th>
                                        <th style="width: 180px;" class="text-end">Subtotal</th>
                                        <th style="width: 70px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody id="purchaseItemsBody">
                                    {{-- Rendered by JS --}}
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">TOTAL</th>
                                        <th class="text-end text-nowrap" id="grandTotalText">
                                            Rp 0
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Harga modal yang diinput di sini akan disimpan sebagai cost batch FIFO.
                        </small>
                    </div>

                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-success">
                            Simpan Pembelian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        @php
            $productsData = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category?->name,
                    'cost_price' => (float) $product->cost_price,
                    'stock' => (int) $product->stock,
                ];
            });
        @endphp

        const products = @json($productsData);

        let itemIndex = 0;

        const oldItems = @json(old('items', []));

        const purchaseItemsBody = document.getElementById('purchaseItemsBody');
        const addItemBtn = document.getElementById('addItemBtn');
        const grandTotalText = document.getElementById('grandTotalText');

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(value || 0);
        }

        function productOptions(selectedProductId = '') {
            let options = '<option value="">-- Pilih Produk --</option>';

            products.forEach(product => {
                const selected = String(product.id) === String(selectedProductId) ? 'selected' : '';

                options += `
                <option value="${product.id}"
                        data-cost-price="${product.cost_price}"
                        ${selected}>
                    ${product.name} - ${product.sku ?? '-'} | Stok: ${product.stock}
                </option>
            `;
            });

            return options;
        }

        function addItemRow(item = {}) {
            const rowIndex = itemIndex++;

            const productId = item.product_id ?? '';
            const qty = item.qty ?? 1;
            const costPrice = item.cost_price ?? '';

            const tr = document.createElement('tr');
            tr.classList.add('purchase-item-row');

            tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]"
                        class="form-select product-select"
                        required>
                    ${productOptions(productId)}
                </select>
            </td>

            <td>
                <input type="number"
                       name="items[${rowIndex}][qty]"
                       class="form-control text-center qty-input"
                       min="1"
                       step="1"
                       value="${qty}"
                       required>
            </td>

            <td>
                <input type="number"
                       name="items[${rowIndex}][cost_price]"
                       class="form-control text-end cost-price-input"
                       min="0"
                       step="1"
                       value="${costPrice}"
                       placeholder="0"
                       required>
            </td>

            <td class="text-end text-nowrap subtotal-text">
                Rp 0
            </td>

            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-item-btn">
                    ×
                </button>
            </td>
        `;

            purchaseItemsBody.appendChild(tr);

            bindRowEvents(tr);
            calculateRow(tr);
            calculateGrandTotal();
        }

        function bindRowEvents(row) {
            const productSelect = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            const costPriceInput = row.querySelector('.cost-price-input');
            const removeButton = row.querySelector('.remove-item-btn');

            productSelect.addEventListener('change', function() {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const defaultCostPrice = selectedOption.dataset.costPrice;

                if (defaultCostPrice && !costPriceInput.value) {
                    costPriceInput.value = Math.round(Number(defaultCostPrice));
                }

                calculateRow(row);
                calculateGrandTotal();
            });

            qtyInput.addEventListener('input', function() {
                calculateRow(row);
                calculateGrandTotal();
            });

            costPriceInput.addEventListener('input', function() {
                calculateRow(row);
                calculateGrandTotal();
            });

            removeButton.addEventListener('click', function() {
                row.remove();

                if (purchaseItemsBody.querySelectorAll('tr').length === 0) {
                    addItemRow();
                }

                calculateGrandTotal();
            });
        }

        function calculateRow(row) {
            const qty = Number(row.querySelector('.qty-input').value || 0);
            const costPrice = Number(row.querySelector('.cost-price-input').value || 0);
            const subtotal = qty * costPrice;

            row.querySelector('.subtotal-text').textContent = formatRupiah(subtotal);
        }

        function calculateGrandTotal() {
            let grandTotal = 0;

            purchaseItemsBody.querySelectorAll('tr').forEach(row => {
                const qty = Number(row.querySelector('.qty-input').value || 0);
                const costPrice = Number(row.querySelector('.cost-price-input').value || 0);

                grandTotal += qty * costPrice;
            });

            grandTotalText.textContent = formatRupiah(grandTotal);
        }

        addItemBtn.addEventListener('click', function() {
            addItemRow();
        });

        if (Array.isArray(oldItems) && oldItems.length > 0) {
            oldItems.forEach(item => addItemRow(item));
        } else {
            addItemRow();
        }
    </script>
@endpush
