<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'type' => [
                'nullable',
                Rule::in([
                    'income',
                    'expense',
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $search = TextNormalizer::singleLine(
            $filters['search'] ?? null
        );

        $categories = FinanceCategory::query()
            ->withCount('transactions')
            ->when(
                $search,
                function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $filters['type'] ?? null,
                fn ($query, $type) => $query->where('type', $type)
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where(
                    'is_active',
                    $status === 'active'
                )
            )
            ->orderByDesc('is_system')
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.finance.categories.index',
            compact('categories')
        );
    }

    public function create(): View
    {
        return view('admin.finance.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeTextInput($request);

        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        FinanceCategory::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_system' => false,
            'is_active' => $request->boolean('is_active'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.finance.categories.index')
            ->with(
                'success',
                'Kategori finance berhasil ditambahkan.'
            );
    }

    public function edit(
        FinanceCategory $category
    ): View {
        return view(
            'admin.finance.categories.edit',
            compact('category')
        );
    }

    public function update(
        Request $request,
        FinanceCategory $category
    ): RedirectResponse {
        $this->normalizeTextInput($request);

        $validated = $request->validate(
            $this->validationRules($category),
            $this->validationMessages()
        );

        $isActive = $request->boolean('is_active');

        if ($category->is_system) {
            if ($validated['code'] !== $category->code) {
                throw ValidationException::withMessages([
                    'code' => 'Kode kategori sistem tidak dapat diubah.',
                ]);
            }

            if ($validated['type'] !== $category->type) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis kategori sistem tidak dapat diubah.',
                ]);
            }

            if (! $isActive) {
                throw ValidationException::withMessages([
                    'is_active' => 'Kategori sistem tidak dapat dinonaktifkan.',
                ]);
            }
        }

        $hasTransactions = $category
            ->transactions()
            ->withTrashed()
            ->exists();

        if (
            $hasTransactions
            && $validated['type'] !== $category->type
        ) {
            throw ValidationException::withMessages([
                'type' => 'Jenis kategori tidak dapat diubah karena sudah digunakan dalam transaksi.',
            ]);
        }

        $category->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.finance.categories.index')
            ->with(
                'success',
                'Kategori finance berhasil diperbarui.'
            );
    }

    public function destroy(
        FinanceCategory $category
    ): RedirectResponse {
        if ($category->is_system) {
            return back()->with(
                'error',
                'Kategori sistem tidak dapat dihapus.'
            );
        }

        if (
            $category->transactions()
                ->withTrashed()
                ->exists()
        ) {
            return back()->with(
                'error',
                'Kategori tidak dapat dihapus karena sudah digunakan dalam transaksi. Nonaktifkan kategori tersebut sebagai gantinya.'
            );
        }

        $category->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        $category->delete();

        return redirect()
            ->route('admin.finance.categories.index')
            ->with(
                'success',
                'Kategori finance berhasil dihapus.'
            );
    }

    private function normalizeTextInput(Request $request): void
    {
        $request->merge([
            'code' => TextNormalizer::singleLine(
                $request->input('code')
            ),
            'name' => TextNormalizer::singleLine(
                $request->input('name')
            ),
        ]);
    }

    private function validationRules(
        ?FinanceCategory $category = null
    ): array {
        $uniqueCodeRule = Rule::unique(
            'finance_categories',
            'code'
        );

        if ($category) {
            $uniqueCodeRule->ignore($category->id);
        }

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                $uniqueCodeRule,
            ],

            'name' => [
                'required',
                'string',
                'max:150',
                "regex:/^[\pL\pN\s.,&()'\/+\-]+$/u",
            ],

            'type' => [
                'required',
                Rule::in([
                    'income',
                    'expense',
                ]),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'code.regex' => 'Kode hanya boleh berisi huruf A-Z, angka, strip, dan underscore.',
            'code.unique' => 'Kode kategori finance sudah digunakan.',
            'name.regex' => 'Nama kategori mengandung karakter yang tidak diizinkan.',
        ];
    }
}
