<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $sku       = $this->generateSku($validated['brand'], $validated['variant'], $validated['size'], $validated['unit']);

        if (Product::where('sku', $sku)->whereNull('deleted_at')->exists()) {
            return back()
                ->withErrors(['brand' => 'Produk dengan kombinasi brand, variant, ukuran, dan satuan ini sudah ada.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $validated, $sku) {
            $product = Product::create([
                ...$this->productFields($request, $validated, $sku),
                'created_by'            => Auth::id(),
                'updated_by'            => Auth::id(),
                'last_stock_updated_by' => Auth::id(),
                'last_stock_updated_at' => now(),
            ]);

            if ($product->stock > 0) {
                StockMovement::create([
                    'product_id'     => $product->id,
                    'type'           => 'in',
                    'qty'            => $product->stock,
                    'previous_stock' => 0,
                    'new_stock'      => $product->stock,
                    'note'           => 'Stok awal produk',
                    'created_by'     => Auth::id(),
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dibuat.');
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
        $sku       = $this->generateSku($validated['brand'], $validated['variant'], $validated['size'], $validated['unit']);

        if (Product::where('sku', $sku)->whereNull('deleted_at')->where('id', '!=', $product->id)->exists()) {
            return back()
                ->withErrors(['brand' => 'Produk dengan kombinasi brand, variant, ukuran, dan satuan ini sudah digunakan produk lain.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $validated, $sku, $product) {
            $oldStock     = $product->stock;
            $newStock     = (int) $validated['stock'];
            $stockChanged = $oldStock !== $newStock;

            $product->update([
                ...$this->productFields($request, $validated, $sku, $product),
                'updated_by'            => Auth::id(),
                'last_stock_updated_by' => $stockChanged ? Auth::id() : $product->last_stock_updated_by,
                'last_stock_updated_at' => $stockChanged ? now() : $product->last_stock_updated_at,
            ]);

            if ($stockChanged) {
                StockMovement::create([
                    'product_id'     => $product->id,
                    'type'           => 'adjustment',
                    'qty'            => $newStock - $oldStock,
                    'previous_stock' => $oldStock,
                    'new_stock'      => $newStock,
                    'note'           => 'Penyesuaian stok dari edit produk',
                    'created_by'     => Auth::id(),
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diubah.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->hasTransactionHistory()) {
            return redirect()
                ->route('admin.products.index')
                ->withErrors(['delete' => 'Produk tidak bisa dihapus karena sudah memiliki histori transaksi, pembelian, batch FIFO, atau log stok. Silakan nonaktifkan produk jika tidak ingin digunakan lagi.']);
        }

        $product->update(['updated_by' => Auth::id()]);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dipindahkan ke trash.');
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

        return redirect()->route('admin.products.trash')->with('success', 'Produk berhasil direstore.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'brand'   => $this->normalizeText($request->brand),
            'variant' => $this->normalizeText($request->variant),
            'unit'    => $this->normalizeText($request->unit),
            'barcode' => $this->normalizeText($request->barcode),
        ]);

        $barcodeUnique = Rule::unique('products', 'barcode')->whereNull('deleted_at');

        if ($product) {
            $barcodeUnique = $barcodeUnique->ignore($product->id);
        }

        return $request->validate([
            'category_id'   => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'brand'         => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9\s\-\.\+\/]+$/'],
            'variant'       => ['required', 'string', 'max:150', 'regex:/^[A-Z0-9\s\-\.\+\/]+$/'],
            'size'          => ['required', 'integer', 'min:1'],
            'unit'          => ['required', 'string', 'max:20', 'regex:/^[A-Z]+$/'],
            'barcode'       => ['nullable', 'string', 'max:255', 'regex:/^[A-Z0-9\-]+$/', $barcodeUnique],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'price'         => ['required', 'numeric', 'min:0', 'gte:cost_price'],
            'stock'         => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active'     => ['nullable', 'boolean'],
        ], $this->validationMessages());
    }

    private function productFields(Request $request, array $validated, string $sku, ?Product $product = null): array
    {
        $imagePath = $product?->image;

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }

            $imagePath = $request->file('image')->store('products', 'public');
        }

        return [
            'category_id'   => $validated['category_id'],
            'brand'         => $validated['brand'],
            'variant'       => $validated['variant'],
            'size'          => $validated['size'],
            'unit'          => $validated['unit'],
            'name'          => $this->generateProductName($validated['brand'], $validated['variant'], $validated['size'], $validated['unit']),
            'sku'           => $sku,
            'barcode'       => $validated['barcode'] ?? null,
            'cost_price'    => $validated['cost_price'],
            'price'         => $validated['price'],
            'stock'         => $validated['stock'],
            'minimum_stock' => $validated['minimum_stock'],
            'image'         => $imagePath,
            'is_active'     => $request->boolean('is_active'),
        ];
    }

    private function validationMessages(): array
    {
        return [
            'brand.regex'   => 'Brand hanya boleh berisi huruf, angka, spasi, strip, titik, plus, dan garis miring.',
            'variant.regex' => 'Variant hanya boleh berisi huruf, angka, spasi, strip, titik, plus, dan garis miring.',
            'unit.regex'    => 'Satuan hanya boleh berisi huruf kapital.',
            'barcode.regex' => 'Barcode hanya boleh berisi huruf, angka, dan strip.',
            'price.gte'     => 'Harga jual tidak boleh lebih kecil dari harga modal.',
        ];
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_strtoupper(preg_replace('/\s+/', ' ', trim($value)));
    }

    private function generateProductName(string $brand, string $variant, int $size, string $unit): string
    {
        return trim("{$brand} {$variant} {$size} {$unit}");
    }

    private function firstLetters(string $value): string
    {
        return mb_substr(preg_replace('/[^A-Z0-9]/', '', $value), 0, 3);
    }

    private function generateSku(string $brand, string $variant, int $size, string $unit): string
    {
        return $this->firstLetters($brand) . '-' . $this->firstLetters($variant) . "-{$size}{$unit}";
    }
}