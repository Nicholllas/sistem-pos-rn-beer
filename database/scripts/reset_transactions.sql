-- ==============================================================================
-- SCRIPT RESET DATA TRANSAKSI & OPERASIONAL (SIAP PAKAI FRESH)
-- Project: Sistem POS RN Beer
-- ==============================================================================
-- Keterangan:
-- Script ini menghapus seluruh data transaksi penjualan, pembelian, batch FIFO,
-- mutasi stok, jurnal kas/keuangan, dan log sistem agar sistem kembali bersih (fresh).
--
-- DATA YANG TETAP DIPERTAHANKAN (AMAN & TIDAK DIHAPUS):
-- 1. users                 -> Akun login Admin & Kasir
-- 2. categories            -> Master kategori produk (Bir, Anggur, dll.)
-- 3. suppliers             -> Master data supplier
-- 4. products              -> Master produk (Nama, Brand, Varian, Kemasan, SKU, Harga Modal, Harga Jual, Gambar, dll.)
-- 5. finance_accounts      -> Master akun kas & bank (Kas Toko, BCA, dll.)
-- 6. finance_categories    -> Master kategori pemasukan & pengeluaran
-- 7. customers             -> Master data pelanggan
-- 8. migrations            -> Riwayat migrasi database Laravel
--
-- EFEK EKSEKUSI SCRIPT INI:
-- 1. Seluruh stok produk di-reset kembali menjadi 0 (stok awal).
-- 2. Penomoran invoice POS, nota pembelian, & transaksi kas kembali mulai dari 1 (#0001).
-- 3. Saldo akun kas/bank akan sinkron kembali ke saldo awal (opening balance).
-- ==============================================================================

-- Nonaktifkan pemeriksaan foreign key sementara untuk truncate yang aman
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. BERSIHKAN DATA PENJUALAN / KASIR (POS)
-- ------------------------------------------------------------------------------
TRUNCATE TABLE `sale_item_batches`;
TRUNCATE TABLE `sale_items`;
TRUNCATE TABLE `sale_payments`;
TRUNCATE TABLE `sales`;

-- ------------------------------------------------------------------------------
-- 2. BERSIHKAN DATA PEMBELIAN STOK (PROCUREMENT)
-- ------------------------------------------------------------------------------
TRUNCATE TABLE `purchase_items`;
TRUNCATE TABLE `purchases`;

-- ------------------------------------------------------------------------------
-- 3. BERSIHKAN INVENTORY, BATCH FIFO & MUTASI STOK
-- ------------------------------------------------------------------------------
TRUNCATE TABLE `stock_batches`;
TRUNCATE TABLE `stock_movements`;

-- ------------------------------------------------------------------------------
-- 4. BERSIHKAN TRANSAKSI KEUANGAN & PENGELUARAN
-- ------------------------------------------------------------------------------
TRUNCATE TABLE `finance_transactions`;
TRUNCATE TABLE `expenses`;

-- ------------------------------------------------------------------------------
-- 5. BERSIHKAN LOG AKTIVITAS & ANTRIAN JOB SISTEM
-- ------------------------------------------------------------------------------
TRUNCATE TABLE `activity_logs`;
TRUNCATE TABLE `failed_jobs`;
TRUNCATE TABLE `job_batches`;
TRUNCATE TABLE `jobs`;
TRUNCATE TABLE `password_reset_tokens`;

-- ------------------------------------------------------------------------------
-- 6. RESET STOK SELURUH PRODUK KE 0 (FRESH STATE)
-- ------------------------------------------------------------------------------
UPDATE `products`
SET 
    `stock` = 0,
    `last_stock_updated_by` = NULL,
    `last_stock_updated_at` = NULL;

-- Aktifkan kembali pemeriksaan foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- SELESAI: Database sekarang bersih dan siap digunakan untuk transaksi nyata!
-- ==============================================================================
