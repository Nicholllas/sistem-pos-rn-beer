<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashierShiftTest extends TestCase
{
    use DatabaseTransactions;

    protected User $cashier;

    protected User $admin;

    protected Product $product;

    protected FinanceAccount $cashAccount;

    protected FinanceAccount $qrisAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->admin = User::factory()->admin()->create([
            'supervisor_pin' => Hash::make('123456'),
        ]);

        $category = Category::firstOrCreate(['name' => 'BEER TEST']);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'BINTANG TEST 620 ML',
            'sku' => 'TEST-BIN-620',
            'brand' => 'BINTANG',
            'variant' => 'PILSNER',
            'size' => 620,
            'unit' => 'ML',
            'cost_price' => 20000,
            'price' => 35000,
            'stock' => 50,
            'minimum_stock' => 5,
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        StockBatch::create([
            'product_id' => $this->product->id,
            'source_type' => 'manual',
            'source_id' => null,
            'qty_in' => 50,
            'qty_remaining' => 50,
            'cost_price' => 20000,
            'batch_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        FinanceCategory::firstOrCreate(
            ['code' => 'SALES'],
            ['name' => 'Pendapatan Penjualan', 'type' => 'income', 'is_system' => true, 'is_active' => true]
        );
        FinanceCategory::firstOrCreate(
            ['code' => 'CASH_SHORTAGE'],
            ['name' => 'SELISIH KAS KURANG (SHIFT)', 'type' => 'expense', 'is_system' => true, 'is_active' => true]
        );
        FinanceCategory::firstOrCreate(
            ['code' => 'CASH_OVERAGE'],
            ['name' => 'SELISIH KAS LEBIH (SHIFT)', 'type' => 'income', 'is_system' => true, 'is_active' => true]
        );

        $this->cashAccount = FinanceAccount::firstOrCreate(
            ['sales_payment_method' => 'cash'],
            ['type' => 'cash', 'code' => '1001', 'name' => 'Kas Toko Kasir', 'opening_balance' => 0, 'is_active' => true]
        );

        $this->qrisAccount = FinanceAccount::firstOrCreate(
            ['sales_payment_method' => 'qris'],
            ['type' => 'bank', 'code' => '1002', 'name' => 'QRIS Kasir', 'opening_balance' => 0, 'is_active' => true]
        );
    }

    public function test_cashier_can_open_shift_with_starting_cash(): void
    {
        $response = $this->actingAs($this->cashier)
            ->post(route('admin.shifts.open'), [
                'starting_cash' => 200000,
            ]);

        $response->assertRedirect(route('admin.sales.pos'));

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $this->cashier->id,
            'starting_cash' => 200000,
            'expected_cash' => 200000,
            'discrepancy_status' => 'none',
            'status' => 'open',
        ]);
    }

    public function test_cashier_cannot_open_multiple_active_shifts(): void
    {
        CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(route('admin.shifts.open'), [
                'starting_cash' => 50000,
            ]);

        $response->assertRedirect(route('admin.sales.pos'));
        $this->assertEquals(1, CashierShift::open()->where('user_id', $this->cashier->id)->count());
    }

    public function test_checkout_is_blocked_if_no_active_shift(): void
    {
        $response = $this->actingAs($this->cashier)
            ->post(route('admin.sales.checkout'), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 35000],
                ],
            ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_checkout_with_change_and_split_payment_calculates_net_cash_correctly(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);

        // Sale 1: Total 70,000, customer pays cash 100,000, change 30,000 -> net cash 70,000
        $res1 = $this->actingAs($this->cashier)
            ->post(route('admin.sales.checkout'), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2],
                ],
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 100000],
                ],
            ]);
        $res1->assertSessionHasNoErrors();

        // Sale 2: Total 70,000, split QRIS 50,000 + Cash 50,000, change 30,000 -> net cash 20,000, QRIS 50,000
        $res2 = $this->actingAs($this->cashier)
            ->post(route('admin.sales.checkout'), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2],
                ],
                'payments' => [
                    ['payment_method' => 'qris', 'amount' => 50000],
                    ['payment_method' => 'cash', 'amount' => 50000],
                ],
            ]);
        $res2->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertEquals(90000, (float) $shift->total_cash_sales);
        $this->assertEquals(50000, (float) $shift->total_qris_sales);
        $this->assertEquals(140000, (float) $shift->total_sales);
        // Expected cash = 100k float + 90k net cash = 190k
        $this->assertEquals(190000, (float) $shift->expected_cash);
    }

    public function test_checkout_rejects_cash_payment_when_non_cash_already_covers_total(): void
    {
        CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);

        // Total is 70,000, QRIS already pays 70,000, yet Cash is also filled 70,000 -> must be rejected
        $response = $this->actingAs($this->cashier)
            ->post(route('admin.sales.checkout'), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2],
                ],
                'payments' => [
                    ['payment_method' => 'qris', 'amount' => 70000],
                    ['payment_method' => 'cash', 'amount' => 70000],
                ],
            ]);

        $response->assertSessionHasErrors('payments');
    }

    public function test_current_shift_endpoint_returns_live_shift_metrics(): void
    {
        CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 150000,
            'expected_cash' => 150000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->cashier)
            ->getJson(route('admin.shifts.current'));

        $response->assertOk()
            ->assertJson([
                'has_active_shift' => true,
                'blind_mode' => true,
                'shift' => [
                    'starting_cash' => 150000,
                ],
            ])
            ->assertJsonMissingPath('shift.expected_cash');
    }

    public function test_cashier_can_close_balanced_shift_without_supervisor_pin(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 200000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(route('admin.shifts.close', $shift), [
                'actual_cash' => 200000,
            ]);

        $response->assertRedirect(route('admin.shifts.summary', $shift));

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals(0, (float) $shift->cash_discrepancy);
        $this->assertEquals('none', $shift->discrepancy_status);
    }

    public function test_cashier_cannot_close_shift_with_discrepancy_without_supervisor_pin(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 200000,
            'status' => 'open',
        ]);

        // Missing PIN
        $response = $this->actingAs($this->cashier)
            ->post(route('admin.shifts.close', $shift), [
                'actual_cash' => 190000,
            ]);

        $response->assertSessionHasErrors('supervisor_pin');
        $shift->refresh();
        $this->assertEquals('open', $shift->status);

        // Wrong PIN
        $responseWrong = $this->actingAs($this->cashier)
            ->post(route('admin.shifts.close', $shift), [
                'actual_cash' => 190000,
                'supervisor_pin' => '999999',
            ]);

        $responseWrong->assertSessionHasErrors('supervisor_pin');
        $shift->refresh();
        $this->assertEquals('open', $shift->status);
    }

    public function test_cashier_can_close_shift_with_discrepancy_using_valid_supervisor_pin_and_admin_can_settle_to_ledger(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 200000,
            'status' => 'open',
        ]);

        // Process a real checkout of 50,000 so Cash Ledger account receives +50,000
        $this->actingAs($this->cashier)
            ->post(route('admin.sales.checkout'), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2], // 70,000
                ],
                'payments' => [
                    ['payment_method' => 'cash', 'amount' => 100000],
                ],
            ]);

        $balanceBeforeAdjustment = (float) $this->cashAccount->fresh()->current_balance;

        // Cashier counts 260,000 instead of expected 270,000 (shortage: -10,000) with Supervisor PIN
        $closeResponse = $this->actingAs($this->cashier)
            ->post(route('admin.shifts.close', $shift), [
                'actual_cash' => 260000,
                'closing_notes' => 'Selisih kurang 10.000 saat tutup shift',
                'supervisor_pin' => '123456',
            ]);

        $closeResponse->assertRedirect(route('admin.shifts.summary', $shift));

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals(-10000, (float) $shift->cash_discrepancy);
        $this->assertEquals('pending', $shift->discrepancy_status);
        $this->assertEquals($this->admin->id, $shift->closed_supervisor_id);

        // Admin settles shortage into Cash Ledger
        $resolveResponse = $this->actingAs($this->admin)
            ->post(route('admin.shifts.resolve-discrepancy', $shift), [
                'resolution' => 'ledger_adjustment',
                'finance_account_id' => $this->cashAccount->id,
                'resolution_notes' => 'Diakui sebagai beban selisih kas toko',
            ]);

        $resolveResponse->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertEquals('resolved', $shift->discrepancy_status);
        $this->assertEquals('ledger_adjustment', $shift->discrepancy_resolution);
        $this->assertNotNull($shift->finance_transaction_id);

        // Cash account balance should be reduced by 10,000
        $this->assertEquals(
            $balanceBeforeAdjustment - 10000,
            (float) $this->cashAccount->fresh()->current_balance
        );

        // Attempting to resolve again should fail validation
        $duplicateResolve = $this->actingAs($this->admin)
            ->post(route('admin.shifts.resolve-discrepancy', $shift), [
                'resolution' => 'manual_resolved',
                'resolution_notes' => 'Coba double settlement',
            ]);
        $duplicateResolve->assertSessionHasErrors('resolution');
    }

    public function test_admin_can_settle_shortage_as_covered_by_cashier_without_modifying_ledger(): void
    {
        $shift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'closed_at' => now(),
            'starting_cash' => 100000,
            'expected_cash' => 150000,
            'actual_cash' => 135000,
            'cash_discrepancy' => -15000,
            'discrepancy_status' => 'pending',
            'status' => 'closed',
        ]);

        $balanceBefore = (float) $this->cashAccount->fresh()->current_balance;

        $response = $this->actingAs($this->admin)
            ->post(route('admin.shifts.resolve-discrepancy', $shift), [
                'resolution' => 'cashier_covered',
                'resolution_notes' => 'Kasir telah mengganti kekurangan Rp 15.000 tunai ke brankas',
            ]);

        $response->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertEquals('resolved', $shift->discrepancy_status);
        $this->assertEquals('cashier_covered', $shift->discrepancy_resolution);
        $this->assertNull($shift->finance_transaction_id);
        $this->assertEquals($balanceBefore, (float) $this->cashAccount->fresh()->current_balance);
    }

    public function test_summary_access_blocks_open_shifts_and_other_cashiers(): void
    {
        $otherCashier = User::factory()->cashier()->create();

        $openShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        // Open shift summary should redirect to POS
        $this->actingAs($this->cashier)
            ->get(route('admin.shifts.summary', $openShift))
            ->assertRedirect(route('admin.sales.pos'));

        // Close shift
        $openShift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'actual_cash' => 100000,
            'cash_discrepancy' => 0,
        ]);

        // Other cashier cannot view this cashier's closing summary
        $this->actingAs($otherCashier)
            ->get(route('admin.shifts.summary', $openShift))
            ->assertForbidden();

        // Owner or Admin can view
        $this->actingAs($this->cashier)
            ->get(route('admin.shifts.summary', $openShift))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.shifts.summary', $openShift))
            ->assertOk();
    }

    public function test_admin_can_record_catchup_sale_to_resolve_shift_overage_without_affecting_active_shift(): void
    {
        $closedShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHours(5),
            'closed_at' => now()->subHours(1),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'actual_cash' => 135000,
            'cash_discrepancy' => 35000,
            'discrepancy_status' => 'pending',
            'status' => 'closed',
        ]);

        // Active shift running concurrently must remain unaffected
        $activeShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);

        $balanceBefore = (float) $this->cashAccount->fresh()->current_balance;

        $response = $this->actingAs($this->admin)
            ->post(route('admin.shifts.catchup-sale.store', $closedShift), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1], // price 35,000 == overage 35,000
                ],
                'resolution_notes' => 'Pelanggan beli 1 botol tunai saat ramai dan kasir lupa input POS',
            ]);

        $response->assertRedirect(route('admin.shifts.summary', $closedShift));

        $closedShift->refresh();
        $activeShift->refresh();
        $this->product->refresh();

        $this->assertEquals('resolved', $closedShift->discrepancy_status);
        $this->assertEquals('catchup_sale', $closedShift->discrepancy_resolution);
        $this->assertNotNull($closedShift->catchup_sale_id);
        $this->assertNotNull($closedShift->finance_transaction_id);

        // Historical closed shift numbers remain immutable
        $this->assertEquals(100000, (float) $closedShift->expected_cash);
        $this->assertEquals(35000, (float) $closedShift->cash_discrepancy);

        // Active shift drawer remains untouched
        $this->assertEquals(100000, (float) $activeShift->expected_cash);
        $this->assertEquals(0, (float) $activeShift->total_cash_sales);

        // Stock decremented cleanly and Cash Ledger gained +35,000
        $this->assertEquals(49, $this->product->stock);
        $this->assertEquals($balanceBefore + 35000, (float) $this->cashAccount->fresh()->current_balance);

        $this->assertDatabaseHas('sales', [
            'id' => $closedShift->catchup_sale_id,
            'cashier_shift_id' => null,
            'settled_shift_id' => $closedShift->id,
            'total' => 35000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
    }

    public function test_catchup_sale_guards_mismatched_total_shortage_shift_and_cashier_access(): void
    {
        $overageShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHours(4),
            'closed_at' => now()->subHours(1),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'actual_cash' => 135000,
            'cash_discrepancy' => 35000,
            'discrepancy_status' => 'pending',
            'status' => 'closed',
        ]);

        // Cashier forbidden (admin only)
        $this->actingAs($this->cashier)
            ->get(route('admin.shifts.catchup-sale.create', $overageShift))
            ->assertForbidden();

        // Total 70,000 != overage 35,000 -> rejected
        $this->actingAs($this->admin)
            ->post(route('admin.shifts.catchup-sale.store', $overageShift), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 2],
                ],
                'resolution_notes' => 'Nominal tidak sesuai selisih',
            ])
            ->assertSessionHasErrors('items');

        // Shortage shift rejected
        $shortageShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHours(6),
            'closed_at' => now()->subHours(5),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'actual_cash' => 90000,
            'cash_discrepancy' => -10000,
            'discrepancy_status' => 'pending',
            'status' => 'closed',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.shifts.catchup-sale.store', $shortageShift), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
                'resolution_notes' => 'Tidak valid untuk selisih kurang',
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_voiding_catchup_sale_reopens_shift_discrepancy_to_pending(): void
    {
        $closedShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHours(5),
            'closed_at' => now()->subHours(1),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'actual_cash' => 135000,
            'cash_discrepancy' => 35000,
            'discrepancy_status' => 'pending',
            'status' => 'closed',
        ]);

        $balanceBefore = (float) $this->cashAccount->fresh()->current_balance;

        $this->actingAs($this->admin)
            ->post(route('admin.shifts.catchup-sale.store', $closedShift), [
                'items' => [
                    ['product_id' => $this->product->id, 'qty' => 1],
                ],
                'resolution_notes' => 'Penjualan susulan test void',
            ]);

        $closedShift->refresh();
        $catchupSale = Sale::findOrFail($closedShift->catchup_sale_id);

        // Now Admin voids that catch-up sale
        $this->actingAs($this->admin)
            ->put(route('admin.sales.void', $catchupSale), [
                'void_reason' => 'Salah pilih produk penjualan susulan',
            ])
            ->assertRedirect(route('admin.sales.receipt', $catchupSale));

        $closedShift->refresh();
        $this->product->refresh();

        $this->assertEquals('pending', $closedShift->discrepancy_status);
        $this->assertNull($closedShift->discrepancy_resolution);
        $this->assertNull($closedShift->catchup_sale_id);
        $this->assertNull($closedShift->finance_transaction_id);
        $this->assertEquals(50, $this->product->stock);
        $this->assertEquals($balanceBefore, (float) $this->cashAccount->fresh()->current_balance);
    }

    public function test_blind_count_hides_expected_cash_from_cashier_but_exposes_to_supervisor_and_admin(): void
    {
        $supervisor = User::factory()->supervisor()->create([
            'supervisor_pin' => Hash::make('999888'),
        ]);

        CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'total_cash_sales' => 35000,
            'expected_cash' => 135000,
            'status' => 'open',
        ]);

        // Cashier receives blind_mode = true without expected_cash or sales sums
        $this->actingAs($this->cashier)
            ->getJson(route('admin.shifts.current'))
            ->assertOk()
            ->assertJson([
                'has_active_shift' => true,
                'blind_mode' => true,
            ])
            ->assertJsonMissingPath('shift.expected_cash')
            ->assertJsonMissingPath('shift.total_cash_sales');

        $this->actingAs($this->cashier)
            ->get(route('admin.sales.pos'))
            ->assertOk()
            ->assertDontSee('Ringkasan Shift');

        // Supervisor receives full shift totals & sees Ringkasan Shift button
        CashierShift::create([
            'user_id' => $supervisor->id,
            'opened_at' => now(),
            'starting_cash' => 150000,
            'status' => 'open',
        ]);

        $this->actingAs($supervisor)
            ->getJson(route('admin.shifts.current'))
            ->assertOk()
            ->assertJson([
                'has_active_shift' => true,
                'blind_mode' => false,
                'shift' => [
                    'starting_cash' => 150000,
                    'expected_cash' => 150000,
                    'total_cash_sales' => 0,
                ],
            ]);

        $this->actingAs($supervisor)
            ->get(route('admin.sales.pos'))
            ->assertOk()
            ->assertSee('Ringkasan Shift');

        // Admin also sees Ringkasan Shift button and full totals when an active shift is open
        CashierShift::create([
            'user_id' => $this->admin->id,
            'opened_at' => now(),
            'starting_cash' => 200000,
            'status' => 'open',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.shifts.current'))
            ->assertOk()
            ->assertJson([
                'has_active_shift' => true,
                'blind_mode' => false,
                'shift' => [
                    'starting_cash' => 200000,
                    'expected_cash' => 200000,
                ],
            ]);

        $this->actingAs($this->admin)
            ->get(route('admin.sales.pos'))
            ->assertOk()
            ->assertSee('Ringkasan Shift');
    }

    public function test_supervisor_can_view_shifts_force_close_cashier_shift_and_cannot_access_admin_only_routes(): void
    {
        $supervisor = User::factory()->supervisor()->create([
            'supervisor_pin' => Hash::make('999888'),
        ]);

        $openShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHours(4),
            'starting_cash' => 100000,
            'total_cash_sales' => 35000,
            'expected_cash' => 135000,
            'status' => 'open',
        ]);

        $this->actingAs($supervisor)
            ->get(route('admin.shifts.index'))
            ->assertOk()
            ->assertSee('Tutup Shift');

        // Supervisor force-closes cashier's abandoned open shift without entering a PIN
        $this->actingAs($supervisor)
            ->post(route('admin.shifts.close', $openShift), [
                'actual_cash' => 135000,
                'closing_notes' => 'Ditutup paksa oleh supervisor karena kasir lupa tutup shift',
            ])
            ->assertRedirect(route('admin.shifts.summary', $openShift));

        $openShift->refresh();
        $this->assertEquals('closed', $openShift->status);
        $this->assertEquals($supervisor->id, $openShift->closed_supervisor_id);

        // Cashier can close discrepant shift using Supervisor's PIN
        $discrepantShift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);

        $this->actingAs($this->cashier)
            ->post(route('admin.shifts.close', $discrepantShift), [
                'actual_cash' => 95000,
                'supervisor_pin' => '999888',
            ])
            ->assertRedirect(route('admin.shifts.summary', $discrepantShift));

        $discrepantShift->refresh();
        $this->assertEquals($supervisor->id, $discrepantShift->closed_supervisor_id);
        $this->assertEquals(-5000, (float) $discrepantShift->cash_discrepancy);

        // Admin-only routes remain 403 Forbidden for Supervisor
        $this->actingAs($supervisor)
            ->post(route('admin.shifts.resolve-discrepancy', $discrepantShift), [
                'resolution_type' => 'manual_note',
                'resolution_notes' => 'Supervisor tidak boleh posting penyelesaian',
            ])
            ->assertForbidden();

        $this->actingAs($supervisor)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }
}
