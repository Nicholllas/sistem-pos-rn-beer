<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashierShift;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Finance\FinancePostingService;
use App\Services\Sales\SaleCheckoutService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashierShiftController extends Controller
{
    public function __construct(
        private readonly FinancePostingService $financePostingService,
        private readonly SaleCheckoutService $saleCheckoutService
    ) {}

    /**
     * Display a listing of shift history and cash reconciliation (Admin view).
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:open,closed'],
            'discrepancy_status' => ['nullable', 'in:none,pending,resolved'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $shifts = CashierShift::query()
            ->with(['user', 'closedBy', 'closedSupervisor', 'resolvedBy', 'financeTransaction.account', 'catchupSale'])
            ->when($filters['cashier_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['discrepancy_status'] ?? null, fn ($q, $ds) => $q->where('discrepancy_status', $ds))
            ->when($filters['start_date'] ?? null, function ($q, $date) {
                $q->where('opened_at', '>=', Carbon::parse($date)->startOfDay());
            })
            ->when($filters['end_date'] ?? null, function ($q, $date) {
                $q->where('opened_at', '<=', Carbon::parse($date)->endOfDay());
            })
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        $cashiers = User::orderBy('name')->get(['id', 'name']);

        $financeAccounts = FinanceAccount::query()
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN sales_payment_method = 'cash' THEN 0 WHEN type = 'cash' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        return view('admin.shifts.index', compact('shifts', 'cashiers', 'financeAccounts'));
    }

    /**
     * Open a new shift for the current cashier.
     */
    public function open(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'starting_cash' => ['required', 'numeric', 'min:0'],
        ], [
            'starting_cash.required' => 'Modal awal kas wajib diisi.',
            'starting_cash.numeric' => 'Modal awal kas harus berupa angka.',
            'starting_cash.min' => 'Modal awal kas tidak boleh bernilai negatif.',
        ]);

        $alreadyOpen = false;

        DB::transaction(function () use ($validated, $request, &$alreadyOpen) {
            User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();

            $existingShift = CashierShift::open()
                ->where('user_id', Auth::id())
                ->first();

            if ($existingShift) {
                $alreadyOpen = true;

                return;
            }

            $shift = CashierShift::create([
                'user_id' => Auth::id(),
                'opened_at' => now(),
                'starting_cash' => $validated['starting_cash'],
                'expected_cash' => $validated['starting_cash'],
                'discrepancy_status' => 'none',
                'status' => 'open',
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'POS Shift',
                'action' => 'open_shift',
                'description' => 'Membuka shift kasir #'.$shift->id.' dengan modal awal Rp '.number_format((float) $validated['starting_cash'], 0, ',', '.'),
                'ip_address' => $request->ip(),
            ]);
        });

        if ($alreadyOpen) {
            return redirect()
                ->route('admin.sales.pos')
                ->with('warning', 'Anda sudah memiliki shift kasir yang sedang aktif.');
        }

        return redirect()
            ->route('admin.sales.pos')
            ->with('success', 'Shift kasir berhasil dibuka. Selamat bertugas!');
    }

    /**
     * Get real-time stats for the current open shift.
     * Blind count enforcement hides expected cash and sales totals from cashier users.
     */
    public function current(Request $request): JsonResponse
    {
        $shift = CashierShift::open()
            ->where('user_id', Auth::id())
            ->with('user')
            ->first();

        if (! $shift) {
            return response()->json([
                'has_active_shift' => false,
            ]);
        }

        if (! Auth::user()->canViewShiftTotals()) {
            return response()->json([
                'has_active_shift' => true,
                'blind_mode' => true,
                'shift' => [
                    'id' => $shift->id,
                    'cashier_name' => $shift->user->name,
                    'opened_at' => $shift->opened_at->format('d/m/Y H:i'),
                    'starting_cash' => (float) $shift->starting_cash,
                ],
            ]);
        }

        $shift->recalculateTotals();

        return response()->json([
            'has_active_shift' => true,
            'blind_mode' => false,
            'shift' => [
                'id' => $shift->id,
                'cashier_name' => $shift->user->name,
                'opened_at' => $shift->opened_at->format('d/m/Y H:i'),
                'starting_cash' => (float) $shift->starting_cash,
                'total_cash_sales' => (float) $shift->total_cash_sales,
                'total_qris_sales' => (float) $shift->total_qris_sales,
                'total_transfer_sales' => (float) $shift->total_transfer_sales,
                'total_sales' => (float) $shift->total_sales,
                'total_cash_refund' => (float) $shift->total_cash_refund,
                'expected_cash' => (float) $shift->expected_cash,
            ],
        ]);
    }

    /**
     * Close the active shift and generate Laporan Penutupan Shift & Rekonsiliasi Kas.
     */
    public function close(Request $request, CashierShift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
            'supervisor_pin' => ['nullable', 'string', 'max:20'],
        ], [
            'actual_cash.required' => 'Nominal uang fisik di laci kas wajib diisi.',
            'actual_cash.numeric' => 'Nominal uang fisik harus berupa angka.',
            'actual_cash.min' => 'Nominal uang fisik tidak boleh minus.',
        ]);

        $alreadyClosed = false;

        DB::transaction(function () use ($shift, $validated, $request, &$alreadyClosed) {
            $shift = CashierShift::whereKey($shift->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($shift->isClosed()) {
                $alreadyClosed = true;

                return;
            }

            if ($shift->user_id !== Auth::id() && ! Auth::user()->isSupervisorOrAdmin()) {
                throw ValidationException::withMessages([
                    'actual_cash' => 'Anda tidak memiliki hak untuk menutup shift kasir lain.',
                ]);
            }

            $shift->recalculateTotals();

            $actualCash = (float) $validated['actual_cash'];
            $discrepancy = $actualCash - (float) $shift->expected_cash;
            $authorizedSupervisor = null;

            if (! Auth::user()->canAuthorizeCashier() && $discrepancy != 0.0) {
                $pin = $validated['supervisor_pin'] ?? null;

                if (! $pin) {
                    throw ValidationException::withMessages([
                        'supervisor_pin' => sprintf(
                            'Terdapat selisih kas sebesar %sRp %s. Penutupan shift dengan selisih kas wajib diotorisasi oleh PIN Supervisor.',
                            $discrepancy < 0 ? '-' : '+',
                            number_format(abs($discrepancy), 0, ',', '.')
                        ),
                    ]);
                }

                $authorizedSupervisor = User::verifySupervisorPin($pin);

                if (! $authorizedSupervisor) {
                    throw ValidationException::withMessages([
                        'supervisor_pin' => 'PIN Supervisor tidak valid atau tidak memiliki hak akses otorisasi.',
                    ]);
                }
            } elseif (Auth::user()->canAuthorizeCashier() && $discrepancy != 0.0) {
                $authorizedSupervisor = Auth::user();
            }

            $shift->update([
                'closed_at' => now(),
                'actual_cash' => $actualCash,
                'cash_discrepancy' => $discrepancy,
                'discrepancy_status' => $discrepancy == 0.0 ? 'none' : 'pending',
                'status' => 'closed',
                'closing_notes' => $validated['closing_notes'] ?? null,
                'closed_by' => Auth::id(),
                'closed_supervisor_id' => $authorizedSupervisor?->id,
            ]);

            $supervisorNote = ($authorizedSupervisor && $authorizedSupervisor->id !== Auth::id())
                ? ' (Diotorisasi oleh Supervisor: '.$authorizedSupervisor->name.')'
                : ($shift->user_id !== Auth::id() ? ' (Ditutup oleh '.Auth::user()->name.')' : '');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'POS Shift',
                'action' => 'close_shift',
                'description' => 'Menutup shift kasir #'.$shift->id.'. Uang Fisik: Rp '.number_format($actualCash, 0, ',', '.').', Selisih: Rp '.number_format($discrepancy, 0, ',', '.').$supervisorNote,
                'ip_address' => $request->ip(),
            ]);
        });

        if ($alreadyClosed) {
            return redirect()
                ->route('admin.shifts.summary', $shift)
                ->with('warning', 'Shift ini sudah pernah ditutup.');
        }

        return redirect()
            ->route('admin.shifts.summary', $shift)
            ->with('success', 'Shift kasir berhasil ditutup dan Laporan Penutupan Shift & Rekonsiliasi Kas telah dibuat.');
    }

    /**
     * Resolve a shift cash discrepancy (Admin only) with optional Cash Ledger adjustment.
     */
    public function resolveDiscrepancy(Request $request, CashierShift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'resolution' => ['required', Rule::in(['ledger_adjustment', 'cashier_covered', 'manual_resolved'])],
            'finance_account_id' => [
                'nullable',
                'required_if:resolution,ledger_adjustment',
                Rule::exists('finance_accounts', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'resolution_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'resolution.required' => 'Jenis penyelesaian selisih wajib dipilih.',
            'finance_account_id.required_if' => 'Akun Cash Ledger tujuan penyesuaian wajib dipilih.',
            'resolution_notes.required' => 'Catatan penyelesaian selisih wajib diisi.',
            'resolution_notes.min' => 'Catatan penyelesaian minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($request, $shift, $validated) {
            $shift = CashierShift::whereKey($shift->id)
                ->with('user')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $shift->isClosed()) {
                throw ValidationException::withMessages([
                    'resolution' => 'Shift belum ditutup.',
                ]);
            }

            if (! $shift->isPendingDiscrepancy()) {
                throw ValidationException::withMessages([
                    'resolution' => 'Selisih shift ini tidak berstatus menunggu penyelesaian.',
                ]);
            }

            $discrepancy = (float) $shift->cash_discrepancy;

            if ($validated['resolution'] === 'cashier_covered' && $discrepancy >= 0.0) {
                throw ValidationException::withMessages([
                    'resolution' => 'Opsi "Diganti oleh Kasir" hanya berlaku untuk selisih kas kurang.',
                ]);
            }

            $financeTransactionId = null;

            if ($validated['resolution'] === 'ledger_adjustment') {
                $account = FinanceAccount::findOrFail((int) $validated['finance_account_id']);
                $transaction = $this->financePostingService->postShiftDiscrepancy(
                    $shift,
                    $account,
                    $validated['resolution_notes']
                );
                $financeTransactionId = $transaction->id;
            }

            $shift->update([
                'discrepancy_status' => 'resolved',
                'discrepancy_resolution' => $validated['resolution'],
                'finance_transaction_id' => $financeTransactionId,
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_notes' => trim($validated['resolution_notes']),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'POS Shift',
                'action' => 'resolve_discrepancy',
                'description' => sprintf(
                    'Menyelesaikan selisih Shift #%d (Rp %s) dengan metode "%s". Catatan: %s',
                    $shift->id,
                    number_format($discrepancy, 0, ',', '.'),
                    $shift->discrepancyResolutionLabel(),
                    trim($validated['resolution_notes'])
                ),
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Selisih kas shift berhasil diselesaikan.');
    }

    /**
     * Display the closing reconciliation report (Laporan Penutupan Shift & Rekonsiliasi Kas).
     */
    public function summary(CashierShift $shift): View|RedirectResponse
    {
        abort_if($shift->user_id !== Auth::id() && ! Auth::user()->isSupervisorOrAdmin(), 403);

        if (! $shift->isClosed()) {
            return redirect()
                ->route('admin.sales.pos')
                ->with('warning', 'Laporan Penutupan Shift & Rekonsiliasi Kas hanya tersedia setelah shift ditutup.');
        }

        $shift->load(['user', 'closedBy', 'closedSupervisor', 'resolvedBy', 'financeTransaction.account', 'catchupSale']);

        $financeAccounts = Auth::user()->isAdmin()
            ? FinanceAccount::query()
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN sales_payment_method = 'cash' THEN 0 WHEN type = 'cash' THEN 1 ELSE 2 END")
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.shifts.summary', compact('shift', 'financeAccounts'));
    }

    /**
     * Show the form to record a catch-up sale resolving a closed shift's cash overage (Admin only).
     */
    public function createCatchupSale(CashierShift $shift): View|RedirectResponse
    {
        if (! $shift->isClosed() || ! $shift->isPendingDiscrepancy() || (float) $shift->cash_discrepancy <= 0.0) {
            return redirect()
                ->route('admin.shifts.index')
                ->with('warning', 'Penjualan susulan hanya tersedia untuk shift tertutup dengan selisih kas lebih yang belum diselesaikan.');
        }

        $shift->load('user');

        $products = Product::with('category')
            ->active()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('admin.shifts.catchup-sale', compact('shift', 'products'));
    }

    /**
     * Store a catch-up sale to resolve a closed shift's cash overage without impacting any active shift drawer.
     */
    public function storeCatchupSale(Request $request, CashierShift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'resolution_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'items.required' => 'Minimal harus ada 1 produk untuk penjualan susulan.',
            'items.min' => 'Minimal harus ada 1 produk untuk penjualan susulan.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.qty.required' => 'Jumlah produk wajib diisi.',
            'items.*.qty.min' => 'Jumlah produk minimal 1.',
            'resolution_notes.required' => 'Catatan penjualan susulan wajib diisi.',
            'resolution_notes.min' => 'Catatan penjualan susulan minimal 5 karakter.',
        ]);

        $sale = DB::transaction(function () use ($request, $shift, $validated) {
            $shift = CashierShift::whereKey($shift->id)
                ->with('user')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $shift->isClosed() || ! $shift->isPendingDiscrepancy() || (float) $shift->cash_discrepancy <= 0.0) {
                throw ValidationException::withMessages([
                    'items' => 'Penjualan susulan hanya dapat dicatat untuk shift tertutup dengan selisih kas lebih yang belum diselesaikan.',
                ]);
            }

            $sale = $this->saleCheckoutService->recordSale(
                $validated['items'],
                [],
                activeShift: null,
                settledShift: $shift,
                movementNoteSuffix: "(Penjualan susulan penyelesaian Shift #{$shift->id})"
            );

            $financeTransaction = FinanceTransaction::query()
                ->where('reference_type', $sale->getMorphClass())
                ->where('reference_id', $sale->id)
                ->where('status', 'posted')
                ->first();

            $shift->update([
                'discrepancy_status' => 'resolved',
                'discrepancy_resolution' => 'catchup_sale',
                'catchup_sale_id' => $sale->id,
                'finance_transaction_id' => $financeTransaction?->id,
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_notes' => trim($validated['resolution_notes']),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'module' => 'POS Shift',
                'action' => 'catchup_sale',
                'description' => sprintf(
                    'Mencatat penjualan susulan %s (Rp %s) untuk menyelesaikan selisih lebih Shift #%d. Catatan: %s',
                    $sale->invoice_number,
                    number_format((float) $sale->total, 0, ',', '.'),
                    $shift->id,
                    trim($validated['resolution_notes'])
                ),
                'ip_address' => $request->ip(),
            ]);

            return $sale;
        });

        return redirect()
            ->route('admin.shifts.summary', $shift)
            ->with('success', "Penjualan susulan {$sale->invoice_number} berhasil dicatat dan selisih lebih Shift #{$shift->id} telah diselesaikan.");
    }
}
