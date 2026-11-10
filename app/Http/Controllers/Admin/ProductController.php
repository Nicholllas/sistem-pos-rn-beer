<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'createdBy', 'updatedBy'])
            ->withCount(['saleItems', 'purchaseItems', 'stockMovements', 'stockBatches'])
            ->latest()
            ->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $sku = $this->generateSku(
            $validated['brand'],
            $validated['variant'],
            $validated['packaging_type'] ?? null,
            $validated['size'],
            $validated['unit']
        );

        if (Product::where('sku', $sku)->whereNull('deleted_at')->exists()) {
            return back()
                ->withErrors([
                    'brand' => "Produk dengan kombinasi identitas ini sudah ada (SKU: {$sku}).",
                ])
                ->withInput();
        }

        Product::create([
            ...$this->productFields($request, $validated, $sku),
            'stock' => 0,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil dibuat. Stok awal produk adalah 0.');
    }

    public function edit(Product $product)
    {
        $product->load(['category', 'createdBy', 'updatedBy', 'lastStockUpdatedBy']);

        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);
        $sku = $this->generateSku(
            $validated['brand'],
            $validated['variant'],
            $validated['packaging_type'] ?? null,
            $validated['size'],
            $validated['unit']
        );

        if (
            Product::where('sku', $sku)
                ->whereNull('deleted_at')
                ->where('id', '!=', $product->id)
                ->exists()
        ) {
            return back()
                ->withErrors([
                    'brand' => "Produk dengan kombinasi identitas ini sudah digunakan produk lain (SKU: {$sku}).",
                ])
                ->withInput();
        }

        $product->update([
            ...$this->productFields($request, $validated, $sku, $product),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil diubah.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->hasTransactionHistory()) {
            return redirect()
                ->route('admin.products.index')
                ->withErrors([
                    'delete' => 'Produk tidak bisa dihapus karena sudah memiliki histori transaksi, pembelian, batch FIFO, atau log stok. Silakan nonaktifkan produk jika tidak ingin digunakan lagi.',
                ]);
        }

        $product->update(['updated_by' => Auth::id()]);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil dipindahkan ke trash.');
    }

    public function trash()
    {
        $products = Product::onlyTrashed()
            ->with(['category', 'createdBy', 'updatedBy'])
            ->latest()
            ->paginate(10);

        return view('admin.products.trash', compact('products'));
    }

    public function restore(string $id): RedirectResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        $product->restore();
        $product->update(['updated_by' => Auth::id()]);

        return redirect()
            ->route('admin.products.trash')
            ->with('success', 'Produk berhasil direstore.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'brand' => $this->normalizeText($request->brand),
            'variant' => $this->normalizeText($request->variant),
            'packaging_type' => $this->normalizeText($request->packaging_type),
            'unit' => $this->normalizeText($request->unit),
            'barcode' => $this->normalizeText($request->barcode),
        ]);

        $barcodeUnique = Rule::unique('products', 'barcode')->whereNull('deleted_at');

        if ($product) {
            $barcodeUnique = $barcodeUnique->ignore($product->id);
        }

        return $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'brand' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9\s\-\.\+\/]+$/'],
            'variant' => ['required', 'string', 'max:150', 'regex:/^[A-Z0-9\s\-\.\+\/]+$/'],
            'packaging_type' => [
                'nullable',
                'string',
                'max:50',
                Rule::in(['BOTOL BESAR', 'BOTOL KECIL', 'KALENG BESAR', 'KALENG KECIL']),
            ],
            'size' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:20', 'regex:/^[A-Z]+$/'],
            'barcode' => ['nullable', 'string', 'max:255', 'regex:/^[A-Z0-9\-]+$/', $barcodeUnique],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0', 'gte:cost_price'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->validationMessages());
    }

    private function productFields(
        Request $request,
        array $validated,
        string $sku,
        ?Product $product = null
    ): array {
        $imagePath = $product?->image;

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }

            $imagePath = $request->file('image')->store('products', 'public');
        }

        return [
            'category_id' => $validated['category_id'],
            'brand' => $validated['brand'],
            'variant' => $validated['variant'],
            'packaging_type' => $validated['packaging_type'] ?? null,
            'size' => $validated['size'],
            'unit' => $validated['unit'],
            'name' => $this->generateProductName(
                $validated['brand'],
                $validated['variant'],
                $validated['packaging_type'] ?? null,
                $validated['size'],
                $validated['unit']
            ),
            'sku' => $sku,
            'barcode' => $validated['barcode'] ?? null,
            'cost_price' => $validated['cost_price'],
            'price' => $validated['price'],
            'minimum_stock' => $validated['minimum_stock'],
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function validationMessages(): array
    {
        return [
            'brand.regex' => 'Brand hanya boleh berisi huruf, angka, spasi, strip, titik, plus, dan garis miring.',
            'variant.regex' => 'Variant hanya boleh berisi huruf, angka, spasi, strip, titik, plus, dan garis miring.',
            'packaging_type.in' => 'Pilihan jenis kemasan tidak valid.',
            'unit.regex' => 'Satuan hanya boleh berisi huruf kapital.',
            'barcode.regex' => 'Barcode hanya boleh berisi huruf, angka, dan strip.',
            'price.gte' => 'Harga jual tidak boleh lebih kecil dari harga modal.',
        ];
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_strtoupper(preg_replace('/\s+/', ' ', trim($value)));
    }

    private function generateProductName(
        string $brand,
        string $variant,
        ?string $packagingType,
        int $size,
        string $unit
    ): string {
        $parts = [$brand, $variant];
        if ($packagingType) {
            $parts[] = $packagingType;
        }
        $parts[] = $size;
        $parts[] = $unit;

        return trim(implode(' ', array_filter($parts)));
    }

    private function brandCode(string $brand): string
    {
        $clean = preg_replace('/[^A-Z0-9]/', '', $brand);
        return mb_substr($clean, 0, 3);
    }

    private function packagingCode(?string $packagingType): ?string
    {
        return match ($packagingType) {
            'BOTOL BESAR' => 'BB',
            'BOTOL KECIL' => 'BK',
            'KALENG BESAR' => 'KB',
            'KALENG KECIL' => 'KK',
            default => null,
        };
    }

    private function variantCode(string $variant): string
    {
        $words = array_values(array_filter(explode(' ', trim($variant))));

        if (empty($words)) {
            return 'VAR';
        }

        if (count($words) === 1) {
            $clean = preg_replace('/[^A-Z0-9]/', '', $words[0]);
            return mb_substr($clean, 0, 3);
        }

        $w1 = preg_replace('/[^A-Z0-9]/', '', $words[0]);
        $w2 = preg_replace('/[^A-Z0-9]/', '', $words[1]);

        if (str_starts_with($w1, 'ANG')) {
            $w2Char = mb_substr($w2, 0, 1);
            return 'AG' . $w2Char;
        }

        if ($w1 === 'BIR') {
            if (str_starts_with($w2, 'HIT')) {
                return 'BHT';
            }
            if (str_starts_with($w2, 'PUT')) {
                return 'BPT';
            }
            $w2Chars = mb_substr($w2, 0, 2);
            return 'B' . $w2Chars;
        }

        if (count($words) >= 3) {
            $w3 = preg_replace('/[^A-Z0-9]/', '', $words[2]);
            return mb_substr($w1, 0, 1) . mb_substr($w2, 0, 1) . mb_substr($w3, 0, 1);
        }

        return mb_substr($w1, 0, 2) . mb_substr($w2, 0, 1);
    }

    private function generateSku(
        string $brand,
        string $variant,
        ?string $packagingType,
        int $size,
        string $unit
    ): string {
        $segments = [
            $this->brandCode($brand),
            $this->variantCode($variant),
        ];

        $pkgCode = $this->packagingCode($packagingType);
        if ($pkgCode) {
            $segments[] = $pkgCode;
        }

        $segments[] = "{$size}{$unit}";

        return implode('-', $segments);
    }
}
