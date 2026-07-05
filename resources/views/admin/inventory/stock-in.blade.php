@extends('layouts.admin')

@section('title', 'Stok Masuk')
@section('page-title', 'Stok Masuk')

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Form Stok Masuk</h3>
        </div>

        <form action="{{ route('admin.inventory.stock-in.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <div class="mb-3">
                    <label for="product_id" class="form-label">Produk</label>

                    <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror"
                        required>
                        <option value="">-- Pilih Produk --</option>

                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->name }} - {{ $product->category?->name }} | Stok: {{ $product->stock }}
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
                    <label for="qty" class="form-label">Jumlah Masuk</label>

                    <input type="number" name="qty" id="qty"
                        class="form-control @error('qty') is-invalid @enderror" value="{{ old('qty') }}" min="1"
                        required>

                    @error('qty')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Contoh: barang masuk 1 dus isi 24, input 24.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="cost_price" class="form-label">
                        Harga Modal per Produk <span class="text-danger">*</span>
                    </label>

                    <input type="number" name="cost_price" id="cost_price"
                        class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price') }}"
                        min="0" step="1" placeholder="Contoh: 15000" required>

                    @error('cost_price')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    <small class="text-muted">
                        Harga ini akan menjadi cost batch FIFO untuk stok masuk manual.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="note" class="form-label">Catatan</label>

                    <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" rows="3"
                        placeholder="Contoh: Barang masuk dari supplier">{{ old('note') }}</textarea>

                    @error('note')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    Simpan Stok Masuk
                </button>
            </div>
        </form>
    </div>

@endsection
