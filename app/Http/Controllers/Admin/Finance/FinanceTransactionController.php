<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'type' => [
                'nullable',
                Rule::in([
                    'income',
                    'expense',
                    'transfer',
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'posted',
                    'void',
                ]),
            ],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_accounts', 'id')
                    ->whereNull('deleted_at'),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_categories', 'id')
                    ->whereNull('deleted_at'),
            ],
        ]);

        $search = TextNormalizer::singleLine(
            $filters['search'] ?? null
        );

        $selectedAccountId = isset($filters['account_id'])
            ? (int) $filters['account_id']
            : null;

        $query = FinanceTransaction::query()
            ->with([
                'account',
                'transferAccount',
                'category',
                'creator',
                'voider',
            ])
            ->when(
                $search,
                function ($query) use ($search) {
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
                $filters['start_date'] ?? null,
                fn ($query, $startDate) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($query, $endDate) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $endDate
                )
            )
            ->when(
                $filters['type'] ?? null,
                fn ($query, $type) => $query->where('type', $type)
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $selectedAccountId,
                function ($query) use ($selectedAccountId) {
                    $query->where(
                        function ($subQuery) use ($selectedAccountId) {
                            $subQuery
                                ->where(
                                    'account_id',
                                    $selectedAccountId
                                )
                                ->orWhere(
                                    'transfer_account_id',
                                    $selectedAccountId
                                );
                        }
                    );
                }
            )
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, $categoryId) => $query->where(
                    'category_id',
                    $categoryId
                )
            );

        $summaryQuery = clone $query;

        $income = (clone $summaryQuery)
            ->where('status', 'posted')
            ->where('type', 'income')
            ->sum('amount');

        $expense = (clone $summaryQuery)
            ->where('status', 'posted')
            ->where('type', 'expense')
            ->sum('amount');

        $transfer = (clone $summaryQuery)
            ->where('status', 'posted')
            ->where('type', 'transfer')
            ->sum('amount');

        $incomingTransfer = 0;
        $outgoingTransfer = 0;

        if ($selectedAccountId !== null) {
            $incomingTransfer = (clone $summaryQuery)
                ->where('status', 'posted')
                ->where('type', 'transfer')
                ->where('transfer_account_id', $selectedAccountId)
                ->sum('amount');

            $outgoingTransfer = (clone $summaryQuery)
                ->where('status', 'posted')
                ->where('type', 'transfer')
                ->where('account_id', $selectedAccountId)
                ->sum('amount');
        }

        $summary = [
            'income' => $income,
            'expense' => $expense,
            'transfer' => $transfer,
            'incoming_transfer' => $incomingTransfer,
            'outgoing_transfer' => $outgoingTransfer,
            'net_cash_flow' => (float) $income
                + (float) $incomingTransfer
                - (float) $expense
                - (float) $outgoingTransfer,
        ];

        $transactions = $query
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        /*
         * Akun dan kategori nonaktif tetap ditampilkan pada filter
         * agar histori lama masih dapat ditelusuri.
         */
        $accounts = FinanceAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $categories = FinanceCategory::query()
            ->orderByDesc('is_active')
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
            ->where('is_system', false)
            ->orderBy('name')
            ->get();

        $expenseCategories = FinanceCategory::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->where('is_system', false)
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
                'before_or_equal:now',
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
                            ->where('is_system', false)
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
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi waktu saat ini.',
            'transfer_account_id.required' => 'Akun tujuan wajib dipilih untuk transaksi transfer.',
            'transfer_account_id.different' => 'Akun sumber dan akun tujuan tidak boleh sama.',
            'category_id.required' => 'Kategori transaksi wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid, tidak aktif, atau merupakan kategori sistem.',
            'reference_number.regex' => 'Nomor referensi mengandung karakter yang tidak diizinkan.',
            'description.regex' => 'Keterangan mengandung karakter yang tidak diizinkan.',
        ]);

        $transaction = DB::transaction(
            function () use ($validated) {
                $lockedAccounts = $this->lockTransactionAccounts(
                    $validated
                );

                $sourceAccount = $lockedAccounts->get(
                    (int) $validated['account_id']
                );

                if (! $sourceAccount) {
                    throw ValidationException::withMessages([
                        'account_id' => 'Akun sumber tidak ditemukan atau sudah tidak aktif.',
                    ]);
                }

                $category = null;

                if ($validated['type'] !== 'transfer') {
                    $category = FinanceCategory::query()
                        ->whereKey($validated['category_id'])
                        ->where('is_active', true)
                        ->where('is_system', false)
                        ->lockForUpdate()
                        ->first();

                    if (! $category) {
                        throw ValidationException::withMessages([
                            'category_id' => 'Kategori tidak valid, tidak aktif, atau merupakan kategori sistem.',
                        ]);
                    }

                    if ($category->type !== $validated['type']) {
                        throw ValidationException::withMessages([
                            'category_id' => 'Jenis kategori tidak sesuai dengan jenis transaksi.',
                        ]);
                    }
                }

                if (in_array(
                    $validated['type'],
                    ['expense', 'transfer'],
                    true
                )) {
                    $this->ensureSufficientBalance(
                        $sourceAccount,
                        (float) $validated['amount']
                    );
                }

                return FinanceTransaction::create([
                    'transaction_number' => $this->generateTransactionNumber(),
                    'transaction_date' => $validated['transaction_date'],
                    'type' => $validated['type'],
                    'account_id' => $sourceAccount->id,
                    'transfer_account_id' => $validated['type'] === 'transfer'
                            ? (int) $validated['transfer_account_id']
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
        if ($financeTransaction->isSystemGenerated()) {
            return back()->with(
                'error',
                'Transaksi otomatis tidak dapat dibatalkan dari Cash Ledger. Batalkan melalui modul penjualan atau pembelian terkait.'
            );
        }

        if ($financeTransaction->isVoid()) {
            return back()->with(
                'error',
                'Transaksi tersebut sudah dibatalkan sebelumnya.'
            );
        }

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
            'void_reason.required' => 'Alasan pembatalan wajib diisi.',
            'void_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
            'void_reason.regex' => 'Alasan pembatalan mengandung karakter yang tidak diizinkan.',
        ]);

        DB::transaction(function () use (
            $financeTransaction,
            $validated
        ) {
            $financeTransaction = FinanceTransaction::query()
                ->whereKey($financeTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($financeTransaction->isSystemGenerated()) {
                throw ValidationException::withMessages([
                    'void_reason' => 'Transaksi otomatis hanya dapat dibatalkan melalui modul sumbernya.',
                ]);
            }

            if ($financeTransaction->isVoid()) {
                throw ValidationException::withMessages([
                    'void_reason' => 'Transaksi tersebut sudah dibatalkan sebelumnya.',
                ]);
            }

            $lockedAccounts = $this->lockAffectedAccounts(
                $financeTransaction
            );

            $this->ensureVoidKeepsBalancesValid(
                $financeTransaction,
                $lockedAccounts
            );

            $financeTransaction->update([
                'status' => 'void',
                'void_reason' => $validated['void_reason'],
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

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

    private function lockTransactionAccounts(
        array $validated
    ): Collection {
        $accountIds = collect([
            (int) $validated['account_id'],
            $validated['type'] === 'transfer'
                ? (int) $validated['transfer_account_id']
                : null,
        ])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $accounts = FinanceAccount::query()
            ->whereIn('id', $accountIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($accounts->count() !== $accountIds->count()) {
            throw ValidationException::withMessages([
                'account_id' => 'Salah satu akun Finance tidak ditemukan atau sudah tidak aktif.',
            ]);
        }

        return $accounts;
    }

    private function lockAffectedAccounts(
        FinanceTransaction $transaction
    ): Collection {
        $accountIds = collect([
            $transaction->account_id,
            $transaction->transfer_account_id,
        ])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return FinanceAccount::query()
            ->whereIn('id', $accountIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function ensureSufficientBalance(
        FinanceAccount $account,
        float $amount
    ): void {
        $currentBalance = (float) $account->current_balance;

        if ($currentBalance < $amount) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Saldo akun %s tidak cukup. Saldo tersedia Rp%s, sedangkan nominal transaksi Rp%s.',
                    $account->name,
                    number_format($currentBalance, 0, ',', '.'),
                    number_format($amount, 0, ',', '.')
                ),
            ]);
        }
    }

    private function ensureVoidKeepsBalancesValid(
        FinanceTransaction $transaction,
        Collection $accounts
    ): void {
        if ($transaction->type === 'income') {
            $account = $accounts->get($transaction->account_id);

            if (! $account) {
                throw ValidationException::withMessages([
                    'void_reason' => 'Akun transaksi tidak ditemukan.',
                ]);
            }

            $balanceAfterVoid =
                (float) $account->current_balance
                - (float) $transaction->amount;

            if ($balanceAfterVoid < 0) {
                throw ValidationException::withMessages([
                    'void_reason' => sprintf(
                        'Transaksi tidak dapat di-void karena saldo akun %s akan menjadi negatif.',
                        $account->name
                    ),
                ]);
            }
        }

        if ($transaction->type === 'transfer') {
            $targetAccount = $accounts->get(
                $transaction->transfer_account_id
            );

            if (! $targetAccount) {
                throw ValidationException::withMessages([
                    'void_reason' => 'Akun tujuan transfer tidak ditemukan.',
                ]);
            }

            $balanceAfterVoid =
                (float) $targetAccount->current_balance
                - (float) $transaction->amount;

            if ($balanceAfterVoid < 0) {
                throw ValidationException::withMessages([
                    'void_reason' => sprintf(
                        'Transfer tidak dapat di-void karena saldo akun tujuan %s akan menjadi negatif.',
                        $targetAccount->name
                    ),
                ]);
            }
        }
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
