<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceAccountController extends Controller
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
                    'cash',
                    'bank',
                    'qris',
                    'ewallet',
                    'other',
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

        $accounts = FinanceAccount::query()
            ->withCount([
                'transactions',
                'incomingTransfers',
            ])
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
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.finance.accounts.index',
            compact('accounts')
        );
    }

    public function create(): View
    {
        return view('admin.finance.accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeTextInput($request);

        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        $this->ensureActiveMappingIsValid(
            $validated,
            $request->boolean('is_active')
        );

        FinanceAccount::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'sales_payment_method' => $validated['sales_payment_method'] ?? null,
            'opening_balance' => $validated['opening_balance'],
            'is_active' => $request->boolean('is_active'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.finance.accounts.index')
            ->with(
                'success',
                'Akun finance berhasil ditambahkan.'
            );
    }

    public function edit(FinanceAccount $account): View
    {
        return view(
            'admin.finance.accounts.edit',
            compact('account')
        );
    }

    public function update(
        Request $request,
        FinanceAccount $account
    ): RedirectResponse {
        $this->normalizeTextInput($request);

        $validated = $request->validate(
            $this->validationRules($account),
            $this->validationMessages()
        );

        $isActive = $request->boolean('is_active');

        $this->ensureActiveMappingIsValid(
            $validated,
            $isActive
        );

        $hasTransactions =
            $account->transactions()
                ->withTrashed()
                ->exists()
            || $account->incomingTransfers()
                ->withTrashed()
                ->exists();

        if ($hasTransactions) {
            if ($validated['code'] !== $account->code) {
                throw ValidationException::withMessages([
                    'code' => 'Kode akun tidak dapat diubah karena sudah memiliki transaksi.',
                ]);
            }

            if ($validated['type'] !== $account->type) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis akun tidak dapat diubah karena sudah memiliki transaksi.',
                ]);
            }

            if ($this->amountsAreDifferent(
                $validated['opening_balance'],
                $account->opening_balance
            )) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Saldo awal tidak dapat diubah setelah akun memiliki transaksi. Gunakan transaksi koreksi.',
                ]);
            }
        }

        $account->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'sales_payment_method' => $validated['sales_payment_method'] ?? null,
            'opening_balance' => $validated['opening_balance'],
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.finance.accounts.index')
            ->with(
                'success',
                'Akun finance berhasil diperbarui.'
            );
    }

    public function destroy(
        FinanceAccount $account
    ): RedirectResponse {
        if ($account->sales_payment_method !== null) {
            return back()->with(
                'error',
                sprintf(
                    'Akun tidak dapat dihapus karena masih menjadi mapping pembayaran %s.',
                    strtoupper($account->sales_payment_method)
                )
            );
        }

        $hasTransactions =
            $account->transactions()
                ->withTrashed()
                ->exists()
            || $account->incomingTransfers()
                ->withTrashed()
                ->exists();

        if ($hasTransactions) {
            return back()->with(
                'error',
                'Akun tidak dapat dihapus karena sudah memiliki riwayat transaksi. Nonaktifkan akun tersebut sebagai gantinya.'
            );
        }

        $account->update([
            'updated_by' => auth()->id(),
            'is_active' => false,
        ]);

        $account->delete();

        return redirect()
            ->route('admin.finance.accounts.index')
            ->with(
                'success',
                'Akun finance berhasil dihapus.'
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
        ?FinanceAccount $account = null
    ): array {
        $uniqueCodeRule = Rule::unique(
            'finance_accounts',
            'code'
        );

        $uniqueSalesPaymentMethodRule = Rule::unique(
            'finance_accounts',
            'sales_payment_method'
        );

        if ($account) {
            $uniqueCodeRule->ignore($account->id);
            $uniqueSalesPaymentMethodRule->ignore($account->id);
        }

        return [
            'code' => [
                'required',
                'string',
                'max:30',
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
                    'cash',
                    'bank',
                    'qris',
                    'ewallet',
                    'other',
                ]),
            ],

            'sales_payment_method' => [
                'nullable',
                Rule::in([
                    'cash',
                    'qris',
                    'transfer',
                ]),
                $uniqueSalesPaymentMethodRule,
            ],

            'opening_balance' => [
                'required',
                'numeric',
                'min:0',
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
            'code.unique' => 'Kode akun finance sudah digunakan.',
            'name.regex' => 'Nama akun mengandung karakter yang tidak diizinkan.',
            'sales_payment_method.unique' => 'Metode pembayaran tersebut sudah dipetakan ke akun Finance lain.',
            'sales_payment_method.in' => 'Mapping metode pembayaran tidak valid.',
        ];
    }

    private function ensureActiveMappingIsValid(
        array $validated,
        bool $isActive
    ): void {
        $salesPaymentMethod =
            $validated['sales_payment_method'] ?? null;

        if ($salesPaymentMethod !== null && ! $isActive) {
            throw ValidationException::withMessages([
                'is_active' => 'Akun yang menjadi mapping pembayaran penjualan harus tetap aktif.',
            ]);
        }
    }

    private function amountsAreDifferent(
        mixed $firstAmount,
        mixed $secondAmount
    ): bool {
        return number_format(
            (float) $firstAmount,
            2,
            '.',
            ''
        ) !== number_format(
            (float) $secondAmount,
            2,
            '.',
            ''
        );
    }
}
