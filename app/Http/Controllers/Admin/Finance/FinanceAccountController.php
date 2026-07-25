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
        $accounts = FinanceAccount::query()
            ->withCount([
                'transactions',
                'incomingTransfers',
            ])
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = TextNormalizer::singleLine(
                        $request->input('search')
                    );

                    if (! $search) {
                        return;
                    }

                    $query->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $request->filled('type'),
                fn ($query) => $query->where(
                    'type',
                    $request->input('type')
                )
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'is_active',
                    $request->input('status') === 'active'
                )
            )
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

        $hasTransactions =
            $account->transactions()
                ->withTrashed()
                ->exists()
            || $account->incomingTransfers()
                ->withTrashed()
                ->exists();

        if ($hasTransactions) {
            $newCode = $validated['code'];

            $newOpeningBalance = (float) $validated['opening_balance'];
            $currentOpeningBalance = (float) $account->opening_balance;

            if ($newCode !== $account->code) {
                throw ValidationException::withMessages([
                    'code' => 'Kode akun tidak dapat diubah karena sudah memiliki transaksi.',
                ]);
            }

            if ($validated['type'] !== $account->type) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis akun tidak dapat diubah karena sudah memiliki transaksi.',
                ]);
            }

            if ($newOpeningBalance !== $currentOpeningBalance) {
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
            'is_active' => $request->boolean('is_active'),
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

        if ($account) {
            $uniqueCodeRule->ignore($account->id);
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
                Rule::unique(
                    'finance_accounts',
                    'sales_payment_method'
                )->ignore($account?->id),
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
}
