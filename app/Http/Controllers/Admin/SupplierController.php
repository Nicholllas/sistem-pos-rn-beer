<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(10);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);

        Supplier::create([
            'name'       => $validated['name'],
            'phone'      => $validated['phone'] ?? null,
            'email'      => $validated['email'] ?? null,
            'address'    => $validated['address'] ?? null,
            'is_active'  => $request->boolean('is_active'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $this->validateSupplier($request, $supplier);

        $supplier->update([
            'name'       => $validated['name'],
            'phone'      => $validated['phone'] ?? null,
            'email'      => $validated['email'] ?? null,
            'address'    => $validated['address'] ?? null,
            'is_active'  => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil diubah.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->hasTransactionHistory()) {
            return redirect()
                ->route('admin.suppliers.index')
                ->withErrors(['delete' => 'Supplier tidak bisa dihapus karena sudah memiliki histori pembelian. Silakan nonaktifkan supplier jika tidak ingin digunakan lagi.']);
        }

        $supplier->update(['updated_by' => Auth::id()]);
        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('success', 'Supplier berhasil dipindahkan ke trash.');
    }

    public function trash()
    {
        $suppliers = Supplier::onlyTrashed()->latest('deleted_at')->paginate(10);

        return view('admin.suppliers.trash', compact('suppliers'));
    }

    public function restore(int $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        $supplier->restore();
        $supplier->update(['is_active' => true, 'updated_by' => Auth::id()]);

        return redirect()->route('admin.suppliers.trash')->with('success', 'Supplier berhasil direstore.');
    }

    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        $request->merge([
            'name'    => $this->normalizeText($request->name),
            'phone'   => $this->normalizePhone($request->phone),
            'email'   => $request->email ? strtolower(trim($request->email)) : null,
            'address' => $this->normalizeText($request->address, false),
        ]);

        $nameUnique = Rule::unique('suppliers', 'name')->whereNull('deleted_at');

        if ($supplier) {
            $nameUnique = $nameUnique->ignore($supplier->id);
        }

        return $request->validate([
            'name'      => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9\s\.\,\-\&\/]+$/', $nameUnique],
            'phone'     => ['nullable', 'string', 'max:30', 'regex:/^[0-9\+\-\s]+$/'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], $this->validationMessages());
    }

    private function normalizeText(?string $value, bool $uppercase = true): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        if ($value === '') {
            return null;
        }

        return $uppercase ? mb_strtoupper($value) : $value;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        return $value === '' ? null : $value;
    }

    private function validationMessages(): array
    {
        return [
            'name.regex'   => 'Nama supplier hanya boleh berisi huruf, angka, spasi, titik, koma, strip, &, dan garis miring.',
            'name.unique'  => 'Nama supplier sudah digunakan.',
            'phone.regex'  => 'Nomor telepon hanya boleh berisi angka, spasi, +, dan strip.',
            'email.email'  => 'Format email supplier tidak valid.',
        ];
    }
}