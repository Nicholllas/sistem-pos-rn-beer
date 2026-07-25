@extends('layouts.admin')

@section('title', 'Tambah Transaksi Finance')
@section('page-title', 'Tambah Transaksi Finance')
@section('page-subtitle', 'Catat pemasukan, pengeluaran, atau transfer dana antar akun.')

@section('page-actions')
    <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-outline-secondary">
        Kembali
    </a>
@endsection

@section('content')
    @include('admin.finance.partials.alerts')

    <div class="crud-page">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">
                <section class="crud-form-panel finance-form-panel">
                    <div class="crud-form-header">
                        <div>
                            <h2>Informasi Transaksi</h2>

                            <p>
                                Lengkapi data transaksi agar saldo akun dan riwayat audit tercatat dengan benar.
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('admin.finance.transactions.store') }}" method="POST" class="js-submit-form">
                        @csrf

                        <div class="crud-form-body">
                            <section class="crud-form-section">
                                <div class="crud-form-section-header">
                                    <h3>Detail Pencatatan</h3>

                                    <p>
                                        Tentukan waktu, jenis, akun, kategori, dan nominal transaksi.
                                    </p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="transaction_date" class="form-label">
                                            Tanggal Transaksi
                                            <span class="crud-required">*</span>
                                        </label>

                                        <input type="datetime-local" name="transaction_date" id="transaction_date"
                                            class="form-control crud-form-control @error('transaction_date') is-invalid @enderror"
                                            value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>

                                        @error('transaction_date')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="type" class="form-label">
                                            Jenis Transaksi
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="type" id="type"
                                            class="form-select crud-form-control @error('type') is-invalid @enderror"
                                            required>
                                            <option value="income" @selected(old('type') === 'income')>
                                                Pemasukan
                                            </option>

                                            <option value="expense" @selected(old('type', 'expense') === 'expense')>
                                                Pengeluaran
                                            </option>

                                            <option value="transfer" @selected(old('type') === 'transfer')>
                                                Transfer Antar Akun
                                            </option>
                                        </select>

                                        @error('type')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="account_id" class="form-label">
                                            <span id="account-label">Akun</span>
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="account_id" id="account_id"
                                            class="form-select crud-form-control @error('account_id') is-invalid @enderror"
                                            required>
                                            <option value="">Pilih akun</option>

                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>
                                                    {{ $account->code }} - {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('account_id')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6" id="transfer-account-wrapper">
                                        <label for="transfer_account_id" class="form-label">
                                            Akun Tujuan
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="transfer_account_id" id="transfer_account_id"
                                            class="form-select crud-form-control @error('transfer_account_id') is-invalid @enderror">
                                            <option value="">Pilih akun tujuan</option>

                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}" @selected((string) old('transfer_account_id') === (string) $account->id)>
                                                    {{ $account->code }} - {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('transfer_account_id')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6" id="category-wrapper">
                                        <label for="category_id" class="form-label">
                                            Kategori
                                            <span class="crud-required">*</span>
                                        </label>

                                        <select name="category_id" id="category_id"
                                            class="form-select crud-form-control @error('category_id') is-invalid @enderror">
                                            <option value="">Pilih kategori</option>

                                            @foreach ($incomeCategories as $category)
                                                <option value="{{ $category->id }}" data-type="income"
                                                    @selected((string) old('category_id') === (string) $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach

                                            @foreach ($expenseCategories as $category)
                                                <option value="{{ $category->id }}" data-type="expense"
                                                    @selected((string) old('category_id') === (string) $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('category_id')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="amount" class="form-label">
                                            Nominal
                                            <span class="crud-required">*</span>
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>

                                            <input type="number" name="amount" id="amount"
                                                class="form-control crud-form-control @error('amount') is-invalid @enderror"
                                                value="{{ old('amount') }}" min="1" step="0.01" placeholder="0"
                                                required>

                                            @error('amount')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="reference_number" class="form-label">
                                            Nomor Referensi
                                        </label>

                                        <input type="text" name="reference_number" id="reference_number"
                                            class="form-control crud-form-control @error('reference_number') is-invalid @enderror"
                                            value="{{ old('reference_number') }}" maxlength="100"
                                            placeholder="Nomor invoice, nota, atau referensi lain" autocomplete="off">

                                        @error('reference_number')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>Opsional, gunakan untuk mempermudah penelusuran dokumen.</span>
                                            <span>Maks. 100 karakter</span>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label">
                                            Keterangan
                                        </label>

                                        <textarea name="description" id="description"
                                            class="form-control crud-form-control @error('description') is-invalid @enderror" rows="4" maxlength="1000"
                                            placeholder="Tambahkan keterangan transaksi">{{ old('description') }}</textarea>

                                        @error('description')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <div class="crud-field-help">
                                            <span>Jelaskan konteks transaksi untuk kebutuhan pemeriksaan.</span>
                                            <span>Maks. 1.000 karakter</span>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="crud-form-footer">
                            <a href="{{ route('admin.finance.transactions.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary js-submit-button"
                                data-loading-text="Menyimpan transaksi...">
                                Simpan Transaksi
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const accountSelect = document.getElementById('account_id');
            const accountLabel = document.getElementById('account-label');

            const categoryWrapper = document.getElementById('category-wrapper');
            const categorySelect = document.getElementById('category_id');

            const transferWrapper = document.getElementById('transfer-account-wrapper');
            const transferSelect = document.getElementById('transfer_account_id');

            function updateCategoryOptions() {
                const selectedType = typeSelect.value;
                const currentCategory = categorySelect.value;

                Array.from(categorySelect.options).forEach(function(option) {
                    if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const matches = option.dataset.type === selectedType;

                    option.hidden = !matches;
                    option.disabled = !matches;
                });

                const selectedOption =
                    categorySelect.options[categorySelect.selectedIndex];

                if (
                    selectedOption &&
                    selectedOption.value &&
                    selectedOption.dataset.type !== selectedType
                ) {
                    categorySelect.value = '';
                }

                if (currentCategory && !categorySelect.value) {
                    categorySelect.value = '';
                }
            }

            function updateTransferAccounts() {
                const sourceAccountId = accountSelect.value;

                Array.from(transferSelect.options).forEach(function(option) {
                    if (!option.value) {
                        return;
                    }

                    option.disabled = option.value === sourceAccountId;
                });

                if (transferSelect.value === sourceAccountId) {
                    transferSelect.value = '';
                }
            }

            function updateForm() {
                const selectedType = typeSelect.value;
                const isTransfer = selectedType === 'transfer';

                transferWrapper.style.display = isTransfer ? '' : 'none';
                transferSelect.required = isTransfer;

                categoryWrapper.style.display = isTransfer ? 'none' : '';
                categorySelect.required = !isTransfer;

                accountLabel.textContent = isTransfer ?
                    'Akun Sumber' :
                    'Akun';

                if (isTransfer) {
                    categorySelect.value = '';
                } else {
                    transferSelect.value = '';
                    updateCategoryOptions();
                }

                updateTransferAccounts();
            }

            typeSelect.addEventListener('change', updateForm);
            accountSelect.addEventListener('change', updateTransferAccounts);

            updateForm();
        });
    </script>
@endpush
