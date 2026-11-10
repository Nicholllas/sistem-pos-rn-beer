@extends('layouts.admin')

@section('title', 'Edit Supplier')
@section('page-title', 'Edit Supplier')
@section('page-subtitle', 'Perbarui identitas, kontak, alamat, dan status supplier.')

@section('page-actions')
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <section class="crud-form-panel">
                    <div class="crud-form-header">
                        <div>
                            <h2>{{ $supplier->name }}</h2>

                            <p>
                                {{ $supplier->phone ?: 'Nomor telepon belum tersedia' }}
                                ·
                                {{ $supplier->is_active ? 'Supplier aktif' : 'Supplier nonaktif' }}
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.suppliers.update', $supplier) }}" method="POST" class="js-submit-form">
                        @csrf
                        @method('PUT')

                        <div class="crud-form-body">
                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Identitas Supplier</h3>

                                    <p>
                                        Nama supplier akan disimpan dalam format huruf kapital.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="name" class="form-label">
                                            Nama Supplier
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="text" name="name" id="name"
                                            class="form-control crud-form-control
                                                @error('name') is-invalid @enderror"
                                            value="{{ old('name', $supplier->name) }}" required maxlength="255"
                                            autocomplete="off" autofocus data-character-counter="supplierNameCounter">

                                        @error('name')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>
                                                Gunakan nama resmi atau nama yang mudah dikenali.
                                            </span>

                                            <span id="supplierNameCounter">
                                                0/255
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Kontak Supplier</h3>

                                    <p>
                                        Perbarui kontak agar proses pemesanan stok lebih mudah.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">
                                            Nomor Telepon / WhatsApp
                                        </label>

                                        <input type="text" name="phone" id="phone"
                                            class="form-control crud-form-control
                                                @error('phone') is-invalid @enderror"
                                            value="{{ old('phone', $supplier->phone) }}" placeholder="Contoh: 081234567890"
                                            autocomplete="off" inputmode="tel">

                                        @error('phone')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">
                                            Email
                                        </label>

                                        <input type="email" name="email" id="email"
                                            class="form-control crud-form-control
                                                @error('email') is-invalid @enderror"
                                            value="{{ old('email', $supplier->email) }}" placeholder="supplier@email.com"
                                            autocomplete="off">

                                        @error('email')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="address" class="form-label">
                                            Alamat
                                        </label>

                                        <textarea name="address" id="address" rows="4"
                                            class="form-control crud-form-control
                                                @error('address') is-invalid @enderror"
                                            placeholder="Masukkan alamat lengkap supplier...">{{ old('address', $supplier->address) }}</textarea>

                                        @error('address')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </section>

                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Status Supplier</h3>

                                    <p>
                                        Supplier nonaktif tidak direkomendasikan dalam pembelian stok.
                                    </p>
                                </div>

                                <div class="crud-status-setting">
                                    <div>
                                        <strong>Supplier Aktif</strong>

                                        <small>
                                            Supplier aktif dapat dipilih dalam transaksi pembelian.
                                        </small>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active" value="0">

                                        <input type="checkbox" name="is_active" id="is_active"
                                            class="form-check-input
                                                @error('is_active') is-invalid @enderror"
                                            value="1" @checked(old('is_active', $supplier->is_active) == '1')>

                                        <label for="is_active" class="form-check-label">
                                            Aktif
                                        </label>
                                    </div>
                                </div>

                                @error('is_active')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </section>

                            <div class="crud-audit-panel">
                                <div class="crud-audit-header">
                                    <h3>Riwayat Supplier</h3>

                                    <p>
                                        Informasi pencatatan dan perubahan data supplier.
                                    </p>
                                </div>

                                <div class="crud-audit-grid">
                                    <div class="crud-audit-item">
                                        <span>Dibuat Oleh</span>

                                        <strong>
                                            {{ $supplier->createdBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Dibuat Pada</span>

                                        <strong>
                                            {{ $supplier->created_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah Oleh</span>

                                        <strong>
                                            {{ $supplier->updatedBy?->name ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="crud-audit-item">
                                        <span>Terakhir Diubah</span>

                                        <strong>
                                            {{ $supplier->updated_at?->format('d M Y H:i') ?? '-' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary js-submit-button"
                                data-loading-text="Menyimpan perubahan...">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
