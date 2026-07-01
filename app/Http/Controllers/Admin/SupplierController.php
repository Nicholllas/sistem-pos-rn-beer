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
        $suppliers = Supplier::latest()
            ->paginate(10);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => $this->normalizeText($request->name),
            'phone' => $this->normalizePhone($request->phone),
            'email' => $request->email ? strtolower(trim($request->email)) : null,
            'address' => $this->normalizeText($request->address, false),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Z0-9\s\.\,\-\&\/]+$/',
                Rule::unique('suppliers', 'name')->whereNull('deleted_at'),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9\+\-\s]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], $this->validationMessages());

        Supplier::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->merge([
            'name' => $this->normalizeText($request->name),
            'phone' => $this->normalizePhone($request->phone),
            'email' => $request->email ? strtolower(trim($request->email)) : null,
            'address' => $this->normalizeText($request->address, false),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Z0-9\s\.\,\-\&\/]+$/',
                Rule::unique('suppliers', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($supplier->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9\+\-\s]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], $this->validationMessages());

        $supplier->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier berhasil diubah.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->update([
            'is_active' => false,
            'updated_by' => Auth::id(),
        ]);

        $supplier->delete();

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier berhasil dipindahkan ke trash.');
    }

    public function trash()
    {
        $suppliers = Supplier::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(10);

        return view('admin.suppliers.trash', compact('suppliers'));
    }

    public function restore(int $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);

        $supplier->restore();

        $supplier->update([
            'is_active' => true,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.suppliers.trash')
            ->with('success', 'Supplier berhasil direstore.');
    }

    private function normalizeText(?string $value, bool $uppercase = true): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strip_tags($value);
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);

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

        $value = strip_tags($value);
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? null : $value;
    }

    private function validationMessages(): array
    {
        return [
            'name.regex' => 'Nama supplier hanya boleh berisi huruf, angka, spasi, titik, koma, strip, &, dan garis miring.',
            'name.unique' => 'Nama supplier sudah digunakan.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, dan strip.',
            'email.email' => 'Format email supplier tidak valid.',
        ];
    }
}