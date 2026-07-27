<?php

namespace App\Services\Finance;

use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class FinanceReconciliationService
{
    public function paginate(
        array $filters,
        int $perPage = 20
    ): array {
        $baseQuery = $this->buildBaseQuery($filters);

        /*
         * reconciliation_status merupakan alias hasil perhitungan
         * dari subquery, bukan kolom pada tabel database.
         */
        $summary = DB::query()
            ->fromSub(
                clone $baseQuery,
                'reconciliation_summary'
            )
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN reconciliation_status = 'matched'
                        THEN 1
                        ELSE 0
                    END
                ) AS matched"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN reconciliation_status = 'unposted'
                        THEN 1
                        ELSE 0
                    END
                ) AS unposted"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN reconciliation_status = 'amount_mismatch'
                        THEN 1
                        ELSE 0
                    END
                ) AS amount_mismatch"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN reconciliation_status = 'status_mismatch'
                        THEN 1
                        ELSE 0
                    END
                ) AS status_mismatch"
            )
            ->first();

        $items = DB::query()
            ->fromSub(
                $baseQuery,
                'reconciliation'
            )
            ->when(
                $filters['status'] ?? null,
                fn (
                    Builder $query,
                    string $status
                ) => $query->where(
                    'reconciliation_status',
                    $status
                )
            )
            ->orderByDesc('source_date')
            ->orderByDesc('source_id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'items' => $items,

            'summary' => [
                'total' => (int) ($summary->total ?? 0),
                'matched' => (int) ($summary->matched ?? 0),
                'unposted' => (int) ($summary->unposted ?? 0),
                'amount_mismatch' => (int) (
                    $summary->amount_mismatch ?? 0
                ),
                'status_mismatch' => (int) (
                    $summary->status_mismatch ?? 0
                ),
            ],
        ];
    }

    private function buildBaseQuery(
        array $filters
    ): Builder {
        $saleMorphClass = (new Sale)->getMorphClass();
        $purchaseMorphClass = (new Purchase)->getMorphClass();

        /*
         * Tabel sales tidak menggunakan SoftDeletes,
         * sehingga tidak boleh menggunakan deleted_at.
         */
        $salesQuery = DB::table('sales')
            ->selectRaw("'sale' AS source_type")
            ->selectRaw(
                '? AS reference_type',
                [$saleMorphClass]
            )
            ->selectRaw('id AS source_id')
            ->selectRaw('invoice_number AS source_number')
            ->selectRaw('created_at AS source_date')
            ->selectRaw('total AS source_amount')
            ->selectRaw('status AS source_status')
            ->selectRaw('NULL AS source_account_id');

        /*
         * Tabel purchases juga tidak menggunakan SoftDeletes
         * berdasarkan model Purchase saat ini.
         */
        $purchasesQuery = DB::table('purchases')
            ->selectRaw("'purchase' AS source_type")
            ->selectRaw(
                '? AS reference_type',
                [$purchaseMorphClass]
            )
            ->selectRaw('id AS source_id')
            ->selectRaw('purchase_number AS source_number')
            ->selectRaw(
                'CAST(purchase_date AS DATETIME) AS source_date'
            )
            ->selectRaw('total AS source_amount')
            ->selectRaw('status AS source_status')
            ->selectRaw(
                'finance_account_id AS source_account_id'
            );

        $sourcesQuery = $salesQuery->unionAll(
            $purchasesQuery
        );

        /*
         * finance_transactions memang menggunakan SoftDeletes,
         * jadi filter deleted_at tetap dipertahankan di sini.
         */
        $financeSummaryQuery = DB::table(
            'finance_transactions'
        )
            ->select([
                'reference_type',
                'reference_id',
            ])
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'posted'
                        THEN amount
                        ELSE 0
                    END
                ) AS posted_amount"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'void'
                        THEN amount
                        ELSE 0
                    END
                ) AS void_amount"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'posted'
                        THEN 1
                        ELSE 0
                    END
                ) AS posted_count"
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN status = 'void'
                        THEN 1
                        ELSE 0
                    END
                ) AS void_count"
            )
            ->selectRaw(
                'COUNT(*) AS transaction_count'
            )
            ->whereNull('deleted_at')
            ->whereNotNull('reference_type')
            ->whereNotNull('reference_id')
            ->groupBy([
                'reference_type',
                'reference_id',
            ]);

        return DB::query()
            ->fromSub(
                $sourcesQuery,
                'sources'
            )
            ->leftJoinSub(
                $financeSummaryQuery,
                'finance_summary',
                function ($join) {
                    $join
                        ->on(
                            'finance_summary.reference_type',
                            '=',
                            'sources.reference_type'
                        )
                        ->on(
                            'finance_summary.reference_id',
                            '=',
                            'sources.source_id'
                        );
                }
            )
            ->select('sources.*')
            ->selectRaw(
                'COALESCE(
                    finance_summary.posted_amount,
                    0
                ) AS posted_amount'
            )
            ->selectRaw(
                'COALESCE(
                    finance_summary.void_amount,
                    0
                ) AS void_amount'
            )
            ->selectRaw(
                'COALESCE(
                    finance_summary.posted_count,
                    0
                ) AS posted_count'
            )
            ->selectRaw(
                'COALESCE(
                    finance_summary.void_count,
                    0
                ) AS void_count'
            )
            ->selectRaw(
                'COALESCE(
                    finance_summary.transaction_count,
                    0
                ) AS transaction_count'
            )
            ->selectRaw(
                'COALESCE(
                    finance_summary.posted_amount,
                    0
                ) - sources.source_amount
                AS difference_amount'
            )
            ->selectRaw(
                "CASE
                    WHEN sources.source_status = 'void'
                        AND COALESCE(
                            finance_summary.posted_count,
                            0
                        ) = 0
                        THEN 'matched'

                    WHEN sources.source_status = 'void'
                        AND COALESCE(
                            finance_summary.posted_count,
                            0
                        ) > 0
                        THEN 'status_mismatch'

                    WHEN sources.source_status = 'completed'
                        AND COALESCE(
                            finance_summary.transaction_count,
                            0
                        ) = 0
                        THEN 'unposted'

                    WHEN sources.source_status = 'completed'
                        AND COALESCE(
                            finance_summary.posted_count,
                            0
                        ) = 0
                        THEN 'status_mismatch'

                    WHEN sources.source_status = 'completed'
                        AND ABS(
                            COALESCE(
                                finance_summary.posted_amount,
                                0
                            ) - sources.source_amount
                        ) < 0.01
                        THEN 'matched'

                    ELSE 'amount_mismatch'
                END AS reconciliation_status"
            )
            ->when(
                $filters['search'] ?? null,
                fn (
                    Builder $query,
                    string $search
                ) => $query->where(
                    'sources.source_number',
                    'like',
                    "%{$search}%"
                )
            )
            ->when(
                $filters['source'] ?? null,
                fn (
                    Builder $query,
                    string $source
                ) => $query->where(
                    'sources.source_type',
                    $source
                )
            )
            ->when(
                $filters['start_date'] ?? null,
                fn (
                    Builder $query,
                    string $startDate
                ) => $query->whereDate(
                    'sources.source_date',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $filters['end_date'] ?? null,
                fn (
                    Builder $query,
                    string $endDate
                ) => $query->whereDate(
                    'sources.source_date',
                    '<=',
                    $endDate
                )
            );
    }
}
