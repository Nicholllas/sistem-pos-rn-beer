<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaleVoidSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $cashier;

    protected User $admin;

    protected Product $product;

    protected Sale $sale;

    protected CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->admin = User::factory()->admin()->create([
            'supervisor_pin' => Hash::make('654321'),
        ]);

        $category = Category::firstOrCreate(['name' => 'VOID TEST CATEGORY']);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'GUINNESS VOID TEST 620 ML',
            'sku' => 'TEST-GUI-620',
            'brand' => 'GUINNESS',
            'variant' => 'STOUT',
            'size' => 620,
            'unit' => 'ML',
            'cost_price' => 25000,
            'price' => 45000,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $batch = StockBatch::create([
            'product_id' => $this->product->id,
            'source_type' => 'manual',
            'source_id' => null,
            'qty_in' => 10,
            'qty_remaining' => 9,
            'cost_price' => 25000,
            'batch_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        FinanceCategory::firstOrCreate(
            ['code' => '4001'],
            ['name' => 'Pendapatan Penjualan', 'type' => 'income', 'is_active' => true]
        );

        FinanceAccount::firstOrCreate(
            ['type' => 'cash'],
            ['code' => '1001', 'name' => 'Kas Toko Kasir', 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]
        );

        $this->shift = CashierShift::create([
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'starting_cash' => 100000,
            'total_cash_sales' => 45000,
            'total_sales' => 45000,
            'expected_cash' => 145000,
            'status' => 'open',
        ]);

        $this->sale = Sale::create([
            'invoice_number' => 'INV-TEST-0001',
            'cashier_shift_id' => $this->shift->id,
            'sold_by' => $this->cashier->id,
            'total' => 45000,
            'paid_amount' => 50000,
            'change_amount' => 5000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $this->sale->id,
            'product_id' => $this->product->id,
            'qty' => 1,
            'price' => 45000,
            'cost_price' => 25000,
            'subtotal' => 45000,
        ]);

        SalePayment::create([
            'sale_id' => $this->sale->id,
            'payment_method' => 'cash',
            'amount' => 50000,
        ]);
    }

    public function test_admin_can_void_sale_without_supervisor_pin(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.sales.void', $this->sale), [
                'void_reason' => 'Admin test void',
            ]);

        $response->assertRedirect(route('admin.sales.receipt', $this->sale));

        $this->sale->refresh();
        $this->assertEquals('void', $this->sale->status);
    }

    public function test_cashier_cannot_void_sale_without_supervisor_pin(): void
    {
        $response = $this->actingAs($this->cashier)
            ->put(route('admin.sales.void', $this->sale), [
                'void_reason' => 'Cashier attempt without pin',
            ]);

        $response->assertSessionHasErrors('supervisor_pin');
        $this->sale->refresh();
        $this->assertEquals('completed', $this->sale->status);
    }

    public function test_cashier_cannot_void_sale_with_wrong_supervisor_pin(): void
    {
        $response = $this->actingAs($this->cashier)
            ->put(route('admin.sales.void', $this->sale), [
                'void_reason' => 'Cashier wrong pin',
                'supervisor_pin' => '000000',
            ]);

        $response->assertSessionHasErrors('supervisor_pin');
        $this->sale->refresh();
        $this->assertEquals('completed', $this->sale->status);
    }

    public function test_cashier_can_void_sale_with_valid_supervisor_pin(): void
    {
        $response = $this->actingAs($this->cashier)
            ->put(route('admin.sales.void', $this->sale), [
                'void_reason' => 'Salah input item pelanggan',
                'supervisor_pin' => '654321',
            ]);

        $response->assertRedirect(route('admin.sales.receipt', $this->sale));

        $this->sale->refresh();
        $this->assertEquals('void', $this->sale->status);
        $this->assertEquals($this->cashier->id, $this->sale->voided_by);

        // Verify shift was updated (cash sales reduced, net cash refund = 45,000 after 5,000 change)
        $this->shift->refresh();
        $this->assertEquals(0, (float) $this->shift->total_cash_sales);
        $this->assertEquals(45000, (float) $this->shift->total_cash_refund);
        $this->assertEquals(100000, (float) $this->shift->expected_cash);

        // Verify ActivityLog was recorded
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Sales',
            'action' => 'void',
            'user_id' => $this->cashier->id,
        ]);
    }

    public function test_voiding_sale_from_closed_shift_does_not_mutate_closed_shift_totals(): void
    {
        $this->shift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'actual_cash' => 145000,
            'cash_discrepancy' => 0,
            'discrepancy_status' => 'none',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.sales.void', $this->sale), [
                'void_reason' => 'Koreksi invoice lama setelah shift tutup',
            ]);

        $response->assertRedirect(route('admin.sales.receipt', $this->sale));

        $this->sale->refresh();
        $this->assertEquals('void', $this->sale->status);

        // Historical closed shift totals must remain immutable
        $this->shift->refresh();
        $this->assertEquals(45000, (float) $this->shift->total_cash_sales);
        $this->assertEquals(145000, (float) $this->shift->expected_cash);
        $this->assertEquals(0, (float) $this->shift->cash_discrepancy);
    }

    public function test_supervisor_can_void_without_pin_and_authorizes_cashier_void_with_pin(): void
    {
        $supervisor = User::factory()->supervisor()->create([
            'supervisor_pin' => Hash::make('777888'),
        ]);

        // Cashier voids using Supervisor's PIN
        $this->actingAs($this->cashier)
            ->put(route('admin.sales.void', $this->sale), [
                'void_reason' => 'Otorisasi supervisor shift malam',
                'supervisor_pin' => '777888',
            ])
            ->assertRedirect(route('admin.sales.receipt', $this->sale));

        $this->assertEquals('void', $this->sale->fresh()->status);

        // Create second sale and verify Supervisor can void directly without PIN
        $secondSale = Sale::create([
            'invoice_number' => 'INV-TEST-VOID-002',
            'cashier_shift_id' => $this->shift->id,
            'sold_by' => $this->cashier->id,
            'total' => 45000,
            'paid_amount' => 45000,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->actingAs($supervisor)
            ->put(route('admin.sales.void', $secondSale), [
                'void_reason' => 'Dibatalkan langsung oleh supervisor',
            ])
            ->assertRedirect(route('admin.sales.receipt', $secondSale));

        $this->assertEquals('void', $secondSale->fresh()->status);
    }
}
