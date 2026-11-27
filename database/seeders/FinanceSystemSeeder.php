<?php

namespace Database\Seeders;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceSystemSeeder extends Seeder
{
    public function run(): void
    {
        FinanceCategory::updateOrCreate(
            [
                'code' => 'SALES',
            ],
            [
                'name' => 'PENJUALAN',
                'type' => 'income',
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        FinanceCategory::updateOrCreate(
            [
                'code' => 'PURCHASE',
            ],
            [
                'name' => 'PEMBELIAN STOK',
                'type' => 'expense',
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        FinanceCategory::updateOrCreate(
            [
                'code' => 'CASH_SHORTAGE',
            ],
            [
                'name' => 'SELISIH KAS KURANG (SHIFT)',
                'type' => 'expense',
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        FinanceCategory::updateOrCreate(
            [
                'code' => 'CASH_OVERAGE',
            ],
            [
                'name' => 'SELISIH KAS LEBIH (SHIFT)',
                'type' => 'income',
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]
        );

        FinanceAccount::updateOrCreate(
            ['code' => '1001'],
            [
                'name' => 'Kas Toko Kasir',
                'type' => 'cash',
                'sales_payment_method' => 'cash',
                'opening_balance' => 0,
                'is_active' => true,
            ]
        );

        FinanceAccount::updateOrCreate(
            ['code' => '1002'],
            [
                'name' => 'QRIS Kasir',
                'type' => 'bank',
                'sales_payment_method' => 'qris',
                'opening_balance' => 0,
                'is_active' => true,
            ]
        );

        FinanceAccount::updateOrCreate(
            ['code' => '1003'],
            [
                'name' => 'Bank Transfer',
                'type' => 'bank',
                'sales_payment_method' => 'transfer',
                'opening_balance' => 0,
                'is_active' => true,
            ]
        );
    }
}
