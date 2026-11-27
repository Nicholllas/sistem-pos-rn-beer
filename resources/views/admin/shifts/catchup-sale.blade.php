@extends('layouts.admin')

@section('title', 'Penjualan Susulan - Shift #' . $shift->id)
@section('page-title', 'Catat Penjualan Susulan')
@section('page-subtitle', 'Rekam transaksi barang terjual yang terlupa diinput pada shift tertutup tanpa memengaruhi kas laci shift aktif.')

@section('page-actions')
    <a href="{{ route('admin.shifts.summary', $shift) }}" class="btn btn-outline-secondary">
        Kembali ke Laporan Shift
    </a>
@endsection

@section('content')
    @php
        $targetOverage = (float) $shift->cash_discrepancy;
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
            'stock' => (int) $p->stock,
        ])->values();
        $oldItems = old('items', [['product_id' => '', 'qty' => 1]]);
    @endphp

    <div class="crud-page purchase-create-page">
        <form action="{{ route('admin.shifts.catchup-sale.store', $shift) }}" method="POST" id="catchupSaleForm" class="purchase-form">
            @csrf

            <div class="purchase-create-layout">
                {{-- Informasi Shift Tertutup --}}
                <aside class="purchase-information-panel">
                    <div class="purchase-information-header">
                        <div>
                            <h2>Informasi Selisih Shift #{{ $shift->id }}</h2>
                            <p>Penjualan susulan otomatis dibayar tunai dari kelebihan kas fisik shift ini.</p>
                        </div>
                    </div>

                    <div class="purchase-information-body">
                        <div class="crud-kv-list">
                            <div class="crud-kv-row">
                                <span>Kasir Shift</span>
                                <strong>{{ $shift->user?->name ?? '-' }}</strong>
                            </div>
                            <div class="crud-kv-row">
                                <span>Waktu Tutup</span>
                                <strong>{{ $shift->closed_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                            </div>
                            <div class="crud-kv-row">
                                <span>Target Selisih Lebih</span>
                                <strong style="color: var(--mui-warning-text);">+Rp {{ number_format($targetOverage, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="purchase-form-group mt-3">
                            <label for="resolution_notes" class="form-label">
                                Catatan Penjualan Susulan
                                <span class="crud-required">*</span>
                            </label>
                            <textarea name="resolution_notes" id="resolution_notes" rows="4" maxlength="1000"
                                class="form-control crud-form-control @error('resolution_notes') is-invalid @enderror"
                                placeholder="Contoh: Pelanggan beli tunai saat ramai, kasir lupa input ke POS pada Shift #{{ $shift->id }}"
                                data-character-counter="catchupNoteCounter" required>{{ old('resolution_notes') }}</textarea>
                            @error('resolution_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="crud-field-help">
                                <span>Tersimpan pada riwayat penyelesaian shift.</span>
                                <span id="catchupNoteCounter">0/1000</span>
                            </div>
                        </div>

                        <div class="purchase-process-info">
                            <strong>Proses otomatis setelah disimpan</strong>
                            <ul>
                                <li>Membuat invoice resmi &amp; masuk Laporan Penjualan.</li>
                                <li>Memotong stok produk &amp; batch FIFO.</li>
                                <li>Memposting pemasukan penjualan ke Cash Ledger.</li>
                                <li>Tidak memengaruhi kas laci shift yang sedang aktif.</li>
                            </ul>
                        </div>
                    </div>
                </aside>

                {{-- Detail Produk Penjualan Susulan --}}
                <section class="crud-panel purchase-items-panel">
                    <div class="crud-panel-header">
                        <div>
                            <h2>Daftar Produk Penjualan Susulan</h2>
                            <p>Pilih produk yang terjual agar totalnya tepat sebesar Rp {{ number_format($targetOverage, 0, ',', '.') }}.</p>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" id="btnAddCatchupRow">
                            Tambah Produk
                        </button>
                    </div>

                    @if ($errors->any())
                        <div class="purchase-items-error" role="alert">
                            <strong>Penjualan susulan belum dapat disimpan.</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="purchase-summary-strip">
                        <div>
                            <span>Target Selisih Lebih</span>
                            <strong>+Rp {{ number_format($targetOverage, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span>Total Penjualan Susulan</span>
                            <strong id="catchupTotalText">Rp 0</strong>
                        </div>
                        <div>
                            <span>Selisih ke Target</span>
                            <strong id="catchupDiffText" style="color: var(--mui-error);">Kurang Rp {{ number_format($targetOverage, 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="purchase-items-grid" id="catchupItemsGrid">
                        <div class="catchup-items-grid-header">
                            <span>Produk</span>
                            <span>Qty</span>
                            <span class="text-end">Subtotal</span>
                            <span class="text-end">Aksi</span>
                        </div>

                        <div class="purchase-items-grid-body" id="catchupItemsBody"></div>

                        <div class="purchase-items-grid-total">
                            <span>Total Penjualan Susulan</span>
                            <strong id="catchupGrandTotalText">Rp 0</strong>
                        </div>
                    </div>

                    <div class="purchase-items-footer">
                        <div>
                            <strong>Pelunasan otomatis dari kelebihan kas</strong>
                            <p>Invoice tercatat lunas tunai tanpa mengubah kas laci shift aktif.</p>
                        </div>

                        <div class="purchase-submit-actions">
                            <a href="{{ route('admin.shifts.summary', $shift) }}" class="btn btn-outline-secondary">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-success" id="btnSubmitCatchupSale">
                                Simpan Penjualan Susulan
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const products = @json($productOptions);
            const targetOverage = {{ $targetOverage }};
            const oldItems = @json($oldItems);
            const tbody = document.getElementById('catchupItemsBody');
            const totalText = document.getElementById('catchupTotalText');
            const grandTotalText = document.getElementById('catchupGrandTotalText');
            const diffText = document.getElementById('catchupDiffText');
            const submitBtn = document.getElementById('btnSubmitCatchupSale');
            const addBtn = document.getElementById('btnAddCatchupRow');
            let rowIndex = 0;

            const formatRp = (num) => 'Rp ' + Number(num || 0).toLocaleString('id-ID');

            function updateTotals() {
                let total = 0;
                tbody.querySelectorAll('.catchup-item-row').forEach((row) => {
                    const select = row.querySelector('.catchup-product-select');
                    const qtyInput = row.querySelector('.catchup-qty-input');
                    const subtotalCell = row.querySelector('.catchup-row-subtotal');
                    const selected = select.options[select.selectedIndex];
                    const price = parseFloat(selected?.dataset.price || '0');
                    const qty = parseInt(qtyInput.value || '0', 10);
                    const subtotal = price * (qty > 0 ? qty : 0);

                    if (subtotalCell) {
                        subtotalCell.textContent = formatRp(subtotal);
                    }
                    total += subtotal;
                });

                totalText.textContent = formatRp(total);
                grandTotalText.textContent = formatRp(total);
                const diff = total - targetOverage;

                if (Math.abs(diff) < 0.01) {
                    diffText.textContent = 'Sesuai Target (Rp 0)';
                    diffText.style.color = 'var(--mui-success)';
                } else if (diff < 0) {
                    diffText.textContent = 'Kurang ' + formatRp(Math.abs(diff));
                    diffText.style.color = 'var(--mui-error)';
                } else {
                    diffText.textContent = 'Lebih ' + formatRp(diff);
                    diffText.style.color = 'var(--mui-error)';
                }

                if (submitBtn) {
                    submitBtn.disabled = Math.abs(diff) > 0.01 || total <= 0;
                }
            }

            function addRow(initialProductId = '', initialQty = 1) {
                const idx = rowIndex++;
                const row = document.createElement('div');
                row.className = 'catchup-item-row';

                const optionsHtml = ['<option value="">Pilih produk</option>']
                    .concat(products.map((p) => {
                        const isSelected = String(p.id) === String(initialProductId) ? 'selected' : '';
                        return `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}" ${isSelected}>${p.name} · Rp ${Number(p.price).toLocaleString('id-ID')} · Stok ${p.stock}</option>`;
                    }))
                    .join('');

                row.innerHTML = `
                    <div class="purchase-item-field">
                        <span class="purchase-item-mobile-label">Produk</span>
                        <select name="items[${idx}][product_id]" class="form-select crud-form-control purchase-product-select catchup-product-select" required>
                            ${optionsHtml}
                        </select>
                    </div>
                    <div class="purchase-item-field">
                        <span class="purchase-item-mobile-label">Qty</span>
                        <input type="number" name="items[${idx}][qty]" class="form-control crud-form-control purchase-qty-input catchup-qty-input" min="1" value="${initialQty}" required>
                    </div>
                    <div class="purchase-item-field">
                        <span class="purchase-item-mobile-label">Subtotal</span>
                        <div class="catchup-row-subtotal">Rp 0</div>
                    </div>
                    <div class="purchase-item-action text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger purchase-remove-item catchup-remove-row" aria-label="Hapus baris">&times;</button>
                    </div>
                `;

                row.querySelector('.catchup-product-select').addEventListener('change', updateTotals);
                row.querySelector('.catchup-qty-input').addEventListener('input', updateTotals);
                row.querySelector('.catchup-remove-row').addEventListener('click', function() {
                    if (tbody.querySelectorAll('.catchup-item-row').length > 1) {
                        row.remove();
                        updateTotals();
                    }
                });

                tbody.appendChild(row);
                updateTotals();
            }

            if (Array.isArray(oldItems) && oldItems.length > 0) {
                oldItems.forEach((item) => addRow(item.product_id || '', item.qty || 1));
            } else {
                addRow();
            }

            addBtn?.addEventListener('click', () => addRow());
        });
    </script>
@endpush
