@extends('layouts.admin')

@section('title', 'Tambah Supplier')
@section('page-title', 'Tambah Supplier')
@section('page-subtitle', 'Daftarkan supplier baru untuk kebutuhan pembelian stok.')

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
                            <h2>Informasi Supplier Baru</h2>

                            <p>
                                Isi identitas dan kontak supplier agar mudah digunakan
                                dalam proses pembelian stok.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.suppliers.store') }}" method="POST" class="js-submit-form">
                        @csrf

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
                                            value="{{ old('name') }}" placeholder="Contoh: PT SUMBER MINUMAN JAYA"
                                            required maxlength="255" autocomplete="off" autofocus
                                            data-character-counter="supplierNameCounter">

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
                                        Informasi kontak bersifat opsional, tetapi disarankan
                                        untuk kebutuhan pemesanan ulang.
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
                                            value="{{ old('phone') }}" placeholder="Contoh: 081234567890"
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
                                            value="{{ old('email') }}" placeholder="supplier@email.com" autocomplete="off">

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
                                            placeholder="Masukkan alamat lengkap supplier...">{{ old('address') }}</textarea>

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
                                        Supplier aktif dapat dipilih pada transaksi pembelian stok.
                                    </p>
                                </div>

                                <div class="crud-status-setting">
                                    <div>
                                        <strong>Supplier Aktif</strong>

                                        <small>
                                            Nonaktifkan jika supplier sedang tidak digunakan.
                                        </small>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active" value="0">

                                        <input type="checkbox" name="is_active" id="is_active"
                                            class="form-check-input
                                                @error('is_active') is-invalid @enderror"
                                            value="1" @checked(old('is_active', '1') == '1')>

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

                            <div class="crud-info-box">
                                <strong>Catatan</strong>

                                <p>
                                    Supplier yang sudah memiliki riwayat pembelian
                                    tidak dapat dihapus untuk menjaga histori transaksi.
                                </p>
                            </div>
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary js-submit-button"
                                data-loading-text="Menyimpan supplier...">
                                Simpan Supplier
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
