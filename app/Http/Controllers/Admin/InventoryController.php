<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function stockIn()
    {
        $products = Product::with('category')->active()->orderBy('name')->get();

        return view('admin.inventory.stock-in', compact('products'));
    }

    public function storeStockIn(Request $request)
    {
        $request->merge(['note' => $this->normalizeNote($request->note)]);

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'qty'  => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            $previousStock = $product->stock;
            $newStock      = $previousStock + $validated['qty'];

            $product->update([
                'stock'                 => $newStock,
                'last_stock_updated_by' => Auth::id(),
                'last_stock_updated_at' => now(),
                'updated_by'            => Auth::id(),
            ]);

            StockMovement::create([
                'product_id'     => $product->id,
                'type'           => 'in',
                'qty'            => $validated['qty'],
                'previous_stock' => $previousStock,
                'new_stock'      => $newStock,
                'note'           => $validated['note'] ?? 'Stok masuk',
                'created_by'     => Auth::id(),
            ]);
        });

        return redirect()->route('admin.inventory.stock-in')->with('success', 'Stok masuk berhasil disimpan.');
    }

    public function adjustment()
    {
        $products = Product::with('category')->active()->orderBy('name')->get();

        return view('admin.inventory.adjustment', compact('products'));
    }

    public function storeAdjustment(Request $request)
    {
        $request->merge(['note' => $this->normalizeNote($request->note)]);

        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'actual_stock' => ['required', 'integer', 'min:0'],
            'note'         => ['required', 'string', 'max:1000'],
        ]);

        $hasChanged = false;

        DB::transaction(function () use ($validated, &$hasChanged) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            $previousStock = $product->stock;
            $newStock      = $validated['actual_stock'];
            $difference    = $newStock - $previousStock;

            if ($difference === 0) {
                return;
            }

            $hasChanged = true;

            $product->update([
                'stock'                 => $newStock,
                'last_stock_updated_by' => Auth::id(),
                'last_stock_updated_at' => now(),
                'updated_by'            => Auth::id(),
            ]);

            StockMovement::create([
                'product_id'     => $product->id,
                'type'           => 'adjustment',
                'qty'            => $difference,
                'previous_stock' => $previousStock,
                'new_stock'      => $newStock,
                'note'           => $validated['note'],
                'created_by'     => Auth::id(),
            ]);
        });

        $message = $hasChanged
            ? 'Penyesuaian stok berhasil disimpan.'
            : 'Stok tidak berubah karena stok fisik sama dengan stok sistem.';

        return redirect()->route('admin.inventory.adjustment')->with('success', $message);
    }

    public function lowStock()
    {
        $products = Product::with(['category', 'lastStockUpdatedBy'])
            ->active()
            ->lowStock()
            ->orderBy('stock')
            ->paginate(10);

        return view('admin.inventory.low-stock', compact('products'));
    }

    public function logs()
    {
        $stockMovements = StockMovement::with(['product.category', 'createdBy'])
            ->latest()
            ->paginate(15);

        return view('admin.inventory.logs', compact('stockMovements'));
    }

    private function normalizeNote(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        return $value === '' ? null : $value;
    }
}