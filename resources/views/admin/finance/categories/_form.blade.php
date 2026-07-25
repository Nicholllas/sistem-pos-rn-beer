@php
    $isEdit = isset($category);
    $selectedType = old('type', $isEdit ? $category->type : 'expense');
    $isActive = old('is_active', $isEdit ? $category->is_active : true);
@endphp

<section class="crud-form-section">
    <div class="crud-form-section-header">
        <h3>Informasi Kategori</h3>

        <p>
            Tentukan identitas, jenis transaksi, dan status penggunaan kategori.
        </p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="code" class="form-label">
                Kode Kategori
                <span class="crud-required">*</span>
            </label>

            <input type="text" name="code" id="code"
                class="form-control crud-form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $isEdit ? $category->code : '') }}" maxlength="50"
                placeholder="Contoh: ELECTRICITY" autocomplete="off" required @readonly($isEdit && $category->is_system)>

            @error('code')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            <div class="crud-field-help">
                <span>
                    @if ($isEdit && $category->is_system)
                        Kode kategori sistem tidak dapat diubah.
                    @else
                        Gunakan huruf, angka, strip, atau underscore.
                    @endif
                </span>
                <span>Maks. 50 karakter</span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="name" class="form-label">
                Nama Kategori
                <span class="crud-required">*</span>
            </label>

            <input type="text" name="name" id="name"
                class="form-control crud-form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $isEdit ? $category->name : '') }}" maxlength="150"
                placeholder="Contoh: Biaya Listrik" autocomplete="off" required>

            @error('name')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            <div class="crud-field-help">
                <span>Nama akan tampil pada form dan riwayat transaksi.</span>
                <span>Maks. 150 karakter</span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <label for="type" class="form-label">
                Jenis Kategori
                <span class="crud-required">*</span>
            </label>

            <select name="type" id="type"
                class="form-select crud-form-control @error('type') is-invalid @enderror" required>
                <option value="income" @selected($selectedType === 'income')>Pemasukan</option>
                <option value="expense" @selected($selectedType === 'expense')>Pengeluaran</option>
            </select>

            @error('type')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            <div class="crud-field-help">
                <span>Jenis tidak dapat diubah setelah kategori digunakan dalam transaksi.</span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="crud-status-setting">
                <div>
                    <strong>Status Kategori</strong>

                    <small>
                        Kategori aktif dapat dipilih saat mencatat transaksi Finance baru.
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

    @if ($isEdit && $category->is_system)
        <div class="crud-info-box">
            <strong>Kategori Sistem</strong>

            <p>
                Kategori ini digunakan untuk integrasi transaksi otomatis dan tidak dapat dihapus.
            </p>
        </div>
    @endif
</section>
