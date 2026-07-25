@php
    $isEdit = isset($account);

    $selectedType = old('type', $isEdit ? $account->type : 'cash');

    $selectedSalesPaymentMethod = old('sales_payment_method', $isEdit ? $account->sales_payment_method : null);

    $isActive = old('is_active', $isEdit ? $account->is_active : true);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">
            Kode Akun <span class="text-danger">*</span>
        </label>

        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
            value="{{ old('code', $isEdit ? $account->code : '') }}" maxlength="30" placeholder="Contoh: CASH-001"
            autocomplete="off" required>

        <div class="form-text">
            Gunakan huruf, angka, strip, atau underscore.
        </div>

        @error('code')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">
            Nama Akun <span class="text-danger">*</span>
        </label>

        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $isEdit ? $account->name : '') }}" maxlength="150" placeholder="Contoh: Kas Toko"
            autocomplete="off" required>

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="type" class="form-label">
            Jenis Akun <span class="text-danger">*</span>
        </label>
        <div class="col-md-6 mb-3">
            <label for="sales_payment_method" class="form-label">
                Mapping Pembayaran Penjualan
            </label>

            <select name="sales_payment_method" id="sales_payment_method"
                class="form-select @error('sales_payment_method') is-invalid @enderror">
                <option value="">
                    Tidak digunakan untuk penjualan
                </option>

                <option value="cash" @selected($selectedSalesPaymentMethod === 'cash')>
                    Pembayaran Cash
                </option>

                <option value="qris" @selected($selectedSalesPaymentMethod === 'qris')>
                    Pembayaran QRIS
                </option>

                <option value="transfer" @selected($selectedSalesPaymentMethod === 'transfer')>
                    Pembayaran Transfer
                </option>
            </select>

            <div class="form-text">
                Setiap metode pembayaran hanya dapat dipetakan ke satu akun.
            </div>

            @error('sales_payment_method')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="cash" @selected($selectedType === 'cash')>
                Kas Tunai
            </option>

            <option value="bank" @selected($selectedType === 'bank')>
                Rekening Bank
            </option>

            <option value="qris" @selected($selectedType === 'qris')>
                Saldo QRIS
            </option>

            <option value="ewallet" @selected($selectedType === 'ewallet')>
                E-Wallet
            </option>

            <option value="other" @selected($selectedType === 'other')>
                Lainnya
            </option>
        </select>

        @error('type')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="opening_balance" class="form-label">
            Saldo Awal <span class="text-danger">*</span>
        </label>

        <div class="input-group">
            <span class="input-group-text">Rp</span>

            <input type="number" name="opening_balance" id="opening_balance"
                class="form-control @error('opening_balance') is-invalid @enderror"
                value="{{ old('opening_balance', $isEdit ? $account->opening_balance : 0) }}" min="0"
                step="0.01" required>

            @error('opening_balance')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        @if ($isEdit)
            <div class="form-text">
                Saldo awal tidak dapat diubah setelah akun memiliki transaksi.
            </div>
        @endif
    </div>

    <div class="col-12 mb-3">
        <input type="hidden" name="is_active" value="0">

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                @checked((bool) $isActive)>

            <label class="form-check-label" for="is_active">
                Akun aktif dan dapat digunakan untuk transaksi
            </label>
        </div>
    </div>
</div>
