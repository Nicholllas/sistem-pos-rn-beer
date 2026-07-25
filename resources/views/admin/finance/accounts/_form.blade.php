@php
    $isEdit = isset($account);
    $selectedType = old('type', $isEdit ? $account->type : 'cash');
    $selectedSalesPaymentMethod = old('sales_payment_method', $isEdit ? $account->sales_payment_method : null);
    $isActive = old('is_active', $isEdit ? $account->is_active : true);
@endphp

<section class="crud-form-section">
    <div class="crud-form-section-header">
        <h3>Informasi Akun</h3>

        <p>
            Tentukan identitas, jenis, saldo pembukaan, dan mapping pembayaran akun.
        </p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="code" class="form-label">
                Kode Akun
                <span class="crud-required">*</span>
            </label>

            <input type="text" name="code" id="code"
                class="form-control crud-form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $isEdit ? $account->code : '') }}" maxlength="30" placeholder="Contoh: CASH-001"
                autocomplete="off" required>

            @error('code')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            <div class="crud-field-help">
                <span>Gunakan huruf, angka, strip, atau underscore.</span>
                <span>Maks. 30 karakter</span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="name" class="form-label">
                Nama Akun
                <span class="crud-required">*</span>
            </label>

            <input type="text" name="name" id="name"
                class="form-control crud-form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $isEdit ? $account->name : '') }}" maxlength="150" placeholder="Contoh: Kas Toko"
                autocomplete="off" required>

            @error('name')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            <div class="crud-field-help">
                <span>Gunakan nama yang mudah dikenali saat memilih akun.</span>
                <span>Maks. 150 karakter</span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="type" class="form-label">
                Jenis Akun
                <span class="crud-required">*</span>
            </label>

            <select name="type" id="type"
                class="form-select crud-form-control @error('type') is-invalid @enderror" required>
                <option value="cash" @selected($selectedType === 'cash')>Kas Tunai</option>
                <option value="bank" @selected($selectedType === 'bank')>Rekening Bank</option>
                <option value="qris" @selected($selectedType === 'qris')>Saldo QRIS</option>
                <option value="ewallet" @selected($selectedType === 'ewallet')>E-Wallet</option>
                <option value="other" @selected($selectedType === 'other')>Lainnya</option>
            </select>

            @error('type')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label for="opening_balance" class="form-label">
                Saldo Awal
                <span class="crud-required">*</span>
            </label>

            <div class="input-group">
                <span class="input-group-text">Rp</span>

                <input type="number" name="opening_balance" id="opening_balance"
                    class="form-control crud-form-control @error('opening_balance') is-invalid @enderror"
                    value="{{ old('opening_balance', $isEdit ? $account->opening_balance : 0) }}" min="0"
                    step="0.01" required>

                @error('opening_balance')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            @if ($isEdit)
                <div class="crud-field-help">
                    <span>Saldo awal tidak dapat diubah setelah akun memiliki transaksi.</span>
                </div>
            @endif
        </div>

        <div class="col-12 col-md-6">
            <label for="sales_payment_method" class="form-label">
                Mapping Pembayaran Penjualan
            </label>

            <select name="sales_payment_method" id="sales_payment_method"
                class="form-select crud-form-control @error('sales_payment_method') is-invalid @enderror">
                <option value="">Tidak digunakan untuk penjualan</option>
                <option value="cash" @selected($selectedSalesPaymentMethod === 'cash')>Pembayaran Cash</option>
                <option value="qris" @selected($selectedSalesPaymentMethod === 'qris')>Pembayaran QRIS</option>
                <option value="transfer" @selected($selectedSalesPaymentMethod === 'transfer')>Pembayaran Transfer</option>
            </select>

            @error('sales_payment_method')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            <div class="crud-field-help">
                <span>Setiap metode pembayaran hanya dapat dipetakan ke satu akun.</span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="crud-status-setting">
                <div>
                    <strong>Status Akun</strong>

                    <small>
                        Akun aktif dapat dipilih pada transaksi Finance dan pembelian stok.
                    </small>
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">

                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        @checked((bool) $isActive)>

                    <label class="form-check-label" for="is_active">
                        Aktif
                    </label>
                </div>
            </div>
        </div>
    </div>
</section>
