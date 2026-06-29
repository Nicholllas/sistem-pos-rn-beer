@extends('layouts.admin')

@section('title', 'Penyesuaian Stok')
@section('page-title', 'Penyesuaian Stok')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Form Penyesuaian Stok</h3>
        </div>

        <form action="{{ route('admin.inventory.adjustment.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <div class="mb-3">
                    <label for="product_id" class="form-label">Produk</label>

                    <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror"
                        required>
                        <option value="">-- Pilih Produk --</option>

                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->name }} - {{ $product->category?->name }} | Stok Sistem: {{ $product->stock }}
                            </option>
                        @endforeach
                    </select>

                    @error('product_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="actual_stock" class="form-label">Stok Fisik Aktual</label>

                    <input type="number" name="actual_stock" id="actual_stock"
                        class="form-control @error('actual_stock') is-invalid @enderror" value="{{ old('actual_stock') }}"
                        min="0" required>

                    @error('actual_stock')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Isi jumlah stok fisik sebenarnya. Sistem akan menghitung selisih otomatis.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="note" class="form-label">Alasan Penyesuaian</label>

                    <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" rows="3"
                        placeholder="Contoh: Selisih hasil stock opname / barang rusak / salah input stok" required>{{ old('note') }}</textarea>

                    @error('note')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Wajib diisi agar audit stok jelas.
                    </small>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Penyesuaian
                </button>
            </div>
        </form>
    </div>

@endsection
