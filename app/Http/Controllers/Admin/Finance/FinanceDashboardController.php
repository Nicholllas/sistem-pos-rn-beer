<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Services\Finance\FinanceReconciliationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __construct(
        private readonly FinanceReconciliationService $reconciliationService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ], [
            'start_date.date' => 'Tanggal awal tidak valid.',
            'end_date.date' => 'Tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : now()->endOfMonth()->endOfDay();

        if ($startDate->diffInDays($endDate) > 366) {
            throw ValidationException::withMessages([
                'end_date' => 'Periode dashboard maksimal 366 hari.',
            ]);
        }

        $accountBalances = $this->getAccountBalances();

        $totalBalance = $accountBalances->sum('current_balance');

        $periodBaseQuery = FinanceTransaction::query()
            ->where('status', 'posted')
            ->whereBetween('transaction_date', [
                $startDate,
                $endDate,
            ]);

        $periodSummary = [
            'income' => (float) (clone $periodBaseQuery)
                ->where('type', 'income')
                ->sum('amount'),

            'expense' => (float) (clone $periodBaseQuery)
                ->where('type', 'expense')
                ->sum('amount'),

            'transfer' => (float) (clone $periodBaseQuery)
                ->where('type', 'transfer')
                ->sum('amount'),
        ];

        $periodSummary['net_cash_flow'] =
            $periodSummary['income']
            - $periodSummary['expense'];

        $dailyCashFlow = $this->getDailyCashFlow(
            $startDate,
            $endDate
        );

        $expenseCategories = FinanceTransaction::query()
            ->leftJoin(
                'finance_categories',
                'finance_categories.id',
                '=',
                'finance_transactions.category_id'
            )
            ->where(
                'finance_transactions.status',
                'posted'
            )
            ->where(
                'finance_transactions.type',
                'expense'
            )
            ->whereBetween(
                'finance_transactions.transaction_date',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->selectRaw(
                "COALESCE(
                    finance_categories.name,
                    'TANPA KATEGORI'
                ) AS category_name"
            )
            ->selectRaw(
                'SUM(finance_transactions.amount) AS total'
            )
            ->groupBy(
                'finance_transactions.category_id',
                'finance_categories.name'
            )
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'category_name' => $row->category_name,
                'total' => (float) $row->total,
            ]);

        $latestTransactions = FinanceTransaction::query()
            ->with([
                'account',
                'transferAccount',
                'category',
                'creator',
            ])
            ->whereBetween('transaction_date', [
                $startDate,
                $endDate,
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(10)
            ->get();

        /*
         * Memakai service rekonsiliasi yang sudah ada agar aturan
         * status Sale/Purchase tidak diduplikasi pada dashboard.
         */
        $reconciliationData = $this->reconciliationService->paginate([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ], 1);

        $reconciliationSummary =
            $reconciliationData['summary'];

        $reconciliationIssueCount =
            (int) $reconciliationSummary['unposted']
            + (int) $reconciliationSummary['amount_mismatch']
            + (int) $reconciliationSummary['status_mismatch'];

        return view(
            'admin.finance.dashboard.index',
            compact(
                'startDate',
                'endDate',
                'accountBalances',
                'totalBalance',
                'periodSummary',
                'dailyCashFlow',
                'expenseCategories',
                'latestTransactions',
                'reconciliationSummary',
                'reconciliationIssueCount'
            )
        );
    }

    private function getAccountBalances()
    {
        return FinanceAccount::query()
            ->withSum([
                'transactions as posted_income' =>
                    fn (Builder $query) => $query
                        ->where('status', 'posted')
                        ->where('type', 'income'),
            ], 'amount')
            ->withSum([
                'transactions as posted_expense' =>
                    fn (Builder $query) => $query
                        ->where('status', 'posted')
                        ->where('type', 'expense'),
            ], 'amount')
            ->withSum([
                'transactions as outgoing_transfer' =>
                    fn (Builder $query) => $query
                        ->where('status', 'posted')
                        ->where('type', 'transfer'),
            ], 'amount')
            ->withSum([
                'incomingTransfers as incoming_transfer' =>
                    fn (Builder $query) => $query
                        ->where('status', 'posted')
                        ->where('type', 'transfer'),
            ], 'amount')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(function (FinanceAccount $account) {
                $openingBalance =
                    (float) $account->opening_balance;

                $income =
                    (float) ($account->posted_income ?? 0);

                $expense =
                    (float) ($account->posted_expense ?? 0);

                $incomingTransfer =
                    (float) ($account->incoming_transfer ?? 0);

                $outgoingTransfer =
                    (float) ($account->outgoing_transfer ?? 0);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'is_active' => (bool) $account->is_active,
                    'opening_balance' => $openingBalance,
                    'income' => $income,
                    'expense' => $expense,
                    'incoming_transfer' => $incomingTransfer,
                    'outgoing_transfer' => $outgoingTransfer,
                    'current_balance' =>
                        $openingBalance
                        + $income
                        + $incomingTransfer
                        - $expense
                        - $outgoingTransfer,
                ];
            });
    }

    private function getDailyCashFlow(
        Carbon $startDate,
        Carbon $endDate
    ) {
        $rows = FinanceTransaction::query()
            ->where('status', 'posted')
            ->whereIn('type', [
                'income',
                'expense',
            ])
            ->whereBetween('transaction_date', [
                $startDate,
                $endDate,
            ])
            ->selectRaw(
                'DATE(transaction_date) AS transaction_day'
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN type = 'income'
                        THEN amount
                        ELSE 0
                    END
                ) AS income"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN type = 'expense'
                        THEN amount
                        ELSE 0
                    END
                ) AS expense"
            )
            ->groupByRaw('DATE(transaction_date)')
            ->orderBy('transaction_day')
            ->get()
            ->keyBy('transaction_day');

        $result = collect();
        $cursor = $startDate->copy()->startOfDay();
        $lastDate = $endDate->copy()->startOfDay();

        while ($cursor->lte($lastDate)) {
            $dateKey = $cursor->toDateString();
            $row = $rows->get($dateKey);

            $income = (float) ($row->income ?? 0);
            $expense = (float) ($row->expense ?? 0);

            $result->push([
                'date' => $dateKey,
                'label' => $cursor->translatedFormat('d M'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ]);

            $cursor->addDay();
        }

        return $result;
    }
}
