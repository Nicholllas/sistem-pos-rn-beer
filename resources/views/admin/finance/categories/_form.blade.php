@php
    $isEdit = isset($category);

    $selectedType = old('type', $isEdit ? $category->type : 'expense');

    $isActive = old('is_active', $isEdit ? $category->is_active : true);
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">
            Kode Kategori <span class="text-danger">*</span>
        </label>

        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror"
            value="{{ old('code', $isEdit ? $category->code : '') }}" maxlength="50" placeholder="Contoh: ELECTRICITY"
            autocomplete="off" required @readonly($isEdit && $category->is_system)>

        <div class="form-text">
            Kode digunakan oleh sistem untuk integrasi transaksi otomatis.
        </div>

        @error('code')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">
            Nama Kategori <span class="text-danger">*</span>
        </label>

        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $isEdit ? $category->name : '') }}" maxlength="150"
            placeholder="Contoh: Biaya Listrik" required>

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="type" class="form-label">
            Jenis Kategori <span class="text-danger">*</span>
        </label>

        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="income" @selected($selectedType === 'income')>
                Pemasukan
            </option>

            <option value="expense" @selected($selectedType === 'expense')>
                Pengeluaran
            </option>
        </select>

        @error('type')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div>
            <input type="hidden" name="is_active" value="0">

            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                    @checked((bool) $isActive)>

                <label class="form-check-label" for="is_active">
                    Kategori aktif
                </label>
            </div>

            @if ($isEdit && $category->is_system)
                <span class="badge bg-primary">
                    Kategori Sistem
                </span>
            @endif
        </div>
    </div>
</div>
