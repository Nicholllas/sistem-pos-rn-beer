<?php

namespace Database\Seeders;

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
    }
}
