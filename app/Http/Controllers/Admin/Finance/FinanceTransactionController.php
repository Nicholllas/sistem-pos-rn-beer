<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = FinanceTransaction::query()
            ->with([
                'account',
                'transferAccount',
                'category',
                'creator',
                'voider',
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
                            ->where(
                                'transaction_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'reference_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->when(
                $request->filled('start_date'),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $request->input('start_date')
                )
            )
            ->when(
                $request->filled('end_date'),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $request->input('end_date')
                )
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
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('account_id'),
                function ($query) use ($request) {
                    $accountId = $request->integer('account_id');

                    $query->where(
                        function ($subQuery) use ($accountId) {
                            $subQuery
                                ->where('account_id', $accountId)
                                ->orWhere(
                                    'transfer_account_id',
                                    $accountId
                                );
                        }
                    );
                }
            )
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where(
                    'category_id',
                    $request->integer('category_id')
                )
            );

        $summaryQuery = clone $query;

        $summary = [
            'income' => (clone $summaryQuery)
                ->where('status', 'posted')
                ->where('type', 'income')
                ->sum('amount'),

            'expense' => (clone $summaryQuery)
                ->where('status', 'posted')
                ->where('type', 'expense')
                ->sum('amount'),

            'transfer' => (clone $summaryQuery)
                ->where('status', 'posted')
                ->where('type', 'transfer')
                ->sum('amount'),
        ];

        $transactions = $query
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $accounts = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = FinanceCategory::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view(
            'admin.finance.transactions.index',
            compact(
                'transactions',
                'accounts',
                'categories',
                'summary'
            )
        );
    }

    public function create(): View
    {
        $accounts = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeCategories = FinanceCategory::query()
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $expenseCategories = FinanceCategory::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'admin.finance.transactions.create',
            compact(
                'accounts',
                'incomeCategories',
                'expenseCategories'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeTransactionInput($request);

        $validated = $request->validate([
            'transaction_date' => [
                'required',
                'date',
            ],

            'type' => [
                'required',
                Rule::in([
                    'income',
                    'expense',
                    'transfer',
                ]),
            ],

            'account_id' => [
                'required',
                Rule::exists('finance_accounts', 'id')
                    ->where(
                        fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->where('is_active', true)
                    ),
            ],

            'transfer_account_id' => [
                'nullable',

                Rule::requiredIf(
                    fn () => $request->input('type') === 'transfer'
                ),

                'different:account_id',

                Rule::exists('finance_accounts', 'id')
                    ->where(
                        fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->where('is_active', true)
                    ),
            ],

            'category_id' => [
                'nullable',

                Rule::requiredIf(
                    fn () => in_array(
                        $request->input('type'),
                        ['income', 'expense'],
                        true
                    )
                ),

                Rule::exists('finance_categories', 'id')
                    ->where(
                        fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->where('is_active', true)
                    ),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Z0-9 ._#\/\-]+$/',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
                "regex:/^[\pL\pN\s.,:;!?()'\"#%&_\/+\-=]+$/u",
            ],
        ], [
            'transfer_account_id.required' => 'Akun tujuan wajib dipilih untuk transaksi transfer.',

            'transfer_account_id.different' => 'Akun sumber dan akun tujuan tidak boleh sama.',

            'category_id.required' => 'Kategori transaksi wajib dipilih.',

            'reference_number.regex' => 'Nomor referensi mengandung karakter yang tidak diizinkan.',

            'description.regex' => 'Keterangan mengandung karakter yang tidak diizinkan.',
        ]);

        $category = null;

        if ($validated['type'] !== 'transfer') {
            $category = FinanceCategory::query()
                ->whereKey($validated['category_id'])
                ->where('is_active', true)
                ->firstOrFail();

            if ($category->type !== $validated['type']) {
                throw ValidationException::withMessages([
                    'category_id' => 'Jenis kategori tidak sesuai dengan jenis transaksi.',
                ]);
            }
        }

        $transaction = DB::transaction(
            function () use ($validated, $category) {
                return FinanceTransaction::create([
                    'transaction_number' => $this->generateTransactionNumber(),

                    'transaction_date' => $validated['transaction_date'],

                    'type' => $validated['type'],

                    'account_id' => $validated['account_id'],

                    'transfer_account_id' => $validated['type'] === 'transfer'
                            ? $validated['transfer_account_id']
                            : null,

                    'category_id' => $category?->id,

                    'amount' => $validated['amount'],

                    'reference_number' => $validated['reference_number'] ?? null,

                    'description' => $validated['description'] ?? null,

                    'status' => 'posted',

                    'created_by' => auth()->id(),

                    'updated_by' => auth()->id(),
                ]);
            }
        );

        return redirect()
            ->route(
                'admin.finance.transactions.show',
                $transaction
            )
            ->with(
                'success',
                'Transaksi finance berhasil dicatat.'
            );
    }

    public function show(
        FinanceTransaction $financeTransaction
    ): View {
        $financeTransaction->load([
            'account',
            'transferAccount',
            'category',
            'creator',
            'updater',
            'voider',
            'reference',
        ]);

        return view(
            'admin.finance.transactions.show',
            compact('financeTransaction')
        );
    }

    public function void(
        Request $request,
        FinanceTransaction $financeTransaction
    ): RedirectResponse {
        $request->merge([
            'void_reason' => TextNormalizer::multiline(
                $request->input('void_reason')
            ),
        ]);

        $validated = $request->validate([
            'void_reason' => [
                'required',
                'string',
                'min:5',
                'max:1000',
                "regex:/^[\pL\pN\s.,:;!?()'\"#%&_\/+\-=]+$/u",
            ],
        ], [
            'void_reason.regex' => 'Alasan pembatalan mengandung karakter yang tidak diizinkan.',
        ]);

        if ($financeTransaction->status === 'void') {
            return back()->with(
                'error',
                'Transaksi tersebut sudah dibatalkan sebelumnya.'
            );
        }

        DB::transaction(
            function () use (
                $financeTransaction,
                $validated
            ) {
                $financeTransaction->update([
                    'status' => 'void',
                    'void_reason' => $validated['void_reason'],
                    'voided_at' => now(),
                    'voided_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        );

        return redirect()
            ->route(
                'admin.finance.transactions.show',
                $financeTransaction
            )
            ->with(
                'success',
                'Transaksi finance berhasil dibatalkan.'
            );
    }

    private function normalizeTransactionInput(
        Request $request
    ): void {
        $request->merge([
            'reference_number' => TextNormalizer::singleLine(
                $request->input('reference_number')
            ),

            'description' => TextNormalizer::multiline(
                $request->input('description')
            ),
        ]);
    }

    private function generateTransactionNumber(): string
    {
        do {
            $number = sprintf(
                'FIN-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );
        } while (
            FinanceTransaction::withTrashed()
                ->where('transaction_number', $number)
                ->exists()
        );

        return $number;
    }
}
