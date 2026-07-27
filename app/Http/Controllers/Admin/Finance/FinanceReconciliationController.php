<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Finance\FinancePostingService;
use App\Services\Finance\FinanceReconciliationService;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceReconciliationController extends Controller
{
    public function __construct(
        private readonly FinanceReconciliationService $reconciliationService,
        private readonly FinancePostingService $postingService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'source' => [
                'nullable',
                Rule::in([
                    'sale',
                    'purchase',
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'matched',
                    'unposted',
                    'amount_mismatch',
                    'status_mismatch',
                ]),
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
        ]);

        if (isset($filters['search'])) {
            $filters['search'] = TextNormalizer::singleLine(
                $filters['search']
            );
        }

        $result = $this->reconciliationService->paginate(
            $filters
        );

        return view(
            'admin.finance.reconciliation.index',
            [
                'items' => $result['items'],
                'summary' => $result['summary'],
                'filters' => $filters,
                'financeAccounts' => FinanceAccount::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),
            ]
        );
    }

    public function repost(
        Request $request,
        string $sourceType,
        int $sourceId
    ): RedirectResponse {
        $validated = $request->validate([
            'finance_account_id' => [
                'nullable',
                Rule::exists('finance_accounts', 'id')
                    ->where(
                        fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->where('is_active', true)
                    ),
            ],
        ]);

        DB::transaction(function () use (
            $sourceType,
            $sourceId,
            $validated
        ) {
            $source = $this->findSourceForUpdate(
                $sourceType,
                $sourceId
            );

            if ($source->status !== 'completed') {
                throw ValidationException::withMessages([
                    'reconciliation' => 'Hanya transaksi sumber berstatus completed yang dapat diposting ulang.',
                ]);
            }

            if (
                $sourceType === 'purchase'
                && ! $source->finance_account_id
            ) {
                if (empty($validated['finance_account_id'])) {
                    throw ValidationException::withMessages([
                        'finance_account_id' => 'Akun pembayaran wajib dipilih untuk memposting ulang pembelian lama.',
                    ]);
                }

                $source->update([
                    'finance_account_id' => $validated['finance_account_id'],
                    'updated_by' => auth()->id(),
                ]);
            }

            $hasFinanceTransaction = FinanceTransaction::withTrashed()
                ->where(
                    'reference_type',
                    $source->getMorphClass()
                )
                ->where('reference_id', $source->getKey())
                ->exists();

            if ($hasFinanceTransaction) {
                throw ValidationException::withMessages([
                    'reconciliation' => 'Posting ulang ditolak karena sumber tersebut sudah memiliki histori transaksi Finance.',
                ]);
            }

            match ($sourceType) {
                'sale' => $this->postingService->postSale($source),
                'purchase' => $this->postingService->postPurchase($source),
            };
        });

        return back()->with(
            'success',
            'Transaksi berhasil diposting ulang ke Finance.'
        );
    }

    public function syncVoid(
        string $sourceType,
        int $sourceId
    ): RedirectResponse {
        DB::transaction(function () use (
            $sourceType,
            $sourceId
        ) {
            $source = $this->findSourceForUpdate(
                $sourceType,
                $sourceId
            );

            if ($source->status !== 'void') {
                throw ValidationException::withMessages([
                    'reconciliation' => 'Sinkronisasi void hanya dapat dilakukan untuk sumber yang sudah berstatus void.',
                ]);
            }

            $reason = sprintf(
                'SINKRONISASI REKONSILIASI FINANCE UNTUK %s',
                $sourceType === 'sale'
                    ? $source->invoice_number
                    : $source->purchase_number
            );

            match ($sourceType) {
                'sale' => $this->postingService->voidSale(
                    $source,
                    $reason
                ),
                'purchase' => $this->postingService->voidPurchase(
                    $source,
                    $reason
                ),
            };
        });

        return back()->with(
            'success',
            'Status transaksi Finance berhasil disinkronkan dengan sumbernya.'
        );
    }

    private function findSourceForUpdate(
        string $sourceType,
        int $sourceId
    ): Sale|Purchase {
        return match ($sourceType) {
            'sale' => Sale::query()
                ->whereKey($sourceId)
                ->lockForUpdate()
                ->firstOrFail(),
            'purchase' => Purchase::query()
                ->whereKey($sourceId)
                ->lockForUpdate()
                ->firstOrFail(),
            default => abort(404),
        };
    }
}
