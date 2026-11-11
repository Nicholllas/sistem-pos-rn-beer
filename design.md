# DESIGN.md - Classy, Modern, & High-Efficiency POS Design System

## 1. Visual Theme & Philosophy
Sistem Point of Sales (POS) ini mengadopsi gaya visual yang **classy, modern, dan profesional**. Antarmuka dirancang khusus untuk alur kerja operasional kasir retail/F&B dengan kecepatan tinggi, akurasi transaksi, dan kenyamanan visual selama jam kerja panjang.

Palet warna terinspirasi dari warna biru terpercaya (*trustworthy blue*) seperti `#1877F2`, dipadukan dengan permukaan kartu putih bersih (*clean white*) dan latar belakang abu-abu sejuk yang sangat lembut (*subtle cool gray*).

### Pembeda Utama dari Media Sosial:
- **Bukan Feeds atau Timeline:** Tidak ada elemen sosial, avatar tanpa fungsi, atau kartu berbasis feed. Seluruh permukaan difokuskan pada kartu inventaris produk, panel transaksi kasir, dan ringkasan pembayaran yang terstruktur rapi.
- **Hierarki Transaksional:** Warna biru terpercaya dicadangkan secara eksklusif untuk aksi prioritas tertinggi (*Primary Call to Action*), seperti tombol Checkout/Pembayaran, tombol Tambah Cepat, dan indikator total akhir.
- **Kesan Elegan & Classy:** Penggunaan bayangan mikro yang sangat lembut (*subtle shadows*), garis batas ultra-halus (1px), radius sudut terukur (8px - 12px), serta tipografi berorientasi angka tabel (*tabular figures*).

---

## 2. Color Palette & Tokens

| Peran | Nilai Hex | Penggunaan & Kontras |
| :--- | :--- | :--- |
| **Base Background** | `#F4F6F8` (Cool Gray) | Latar dasar seluruh halaman, meredam silau layar monitor kasir. |
| **Card & Surface** | `#FFFFFF` (Clean White) | Area produk, katalog, keranjang belanja, dan modal transaksi. Memberikan batas visual bersih dari background. |
| **Primary CTA (Trustworthy Blue)** | `#1877F2` | **Hanya untuk tombol aksi utama** ("Checkout", "Bayar", "Tambah Item", dan filter aktif). Hover: `#166FE5`, Active: `#0055D6`. |
| **Primary CTA Text** | `#FFFFFF` | Teks putih dengan kontras tinggi di atas biru utama (rasio kontras 4.2:1 untuk teks tebal/besar). |
| **Success / Kembalian** | `#10B981` / `#059669` | Indikator nominal kembalian pelanggan, status pembayaran lunas, dan stok aman. Background lembut: `#ECFDF5`. |
| **Warning / Stok Rendah** | `#F59E0B` / `#D97706` | Peringatan stok minimum produk dan status pembayaran belum lengkap. Background lembut: `#FFFBEB`. |
| **Error / Alert** | `#EF4444` / `#DC2626` | Pesan validasi gagal dan pembatalan transaksi. Background lembut: `#FEF2F2`. |
| **Text Primary** | `#0F172A` (Slate 900) | Judul produk, nominal total transaksi, dan teks utama dengan kontras tinggi (WCAG AAA > 14:1). |
| **Text Secondary / Meta** | `#64748B` (Slate 500) | Kategori, SKU, barcode, dan keterangan pendukung. |
| **Border & Divider** | `#E2E8F0` / `#E5E7EB` | Garis pemisah tipis dan border kartu yang bersih dan tidak mencolok. |
| **Input Focus Ring** | `#1877F2` (2px) | Ring fokus biru cerah dengan aksen lembut `#E7F3FF` untuk navigasi keyboard kasir yang presisi. |

---

## 3. Layout Principles & Ergonomics (Compact & Dense)

### Prinsip Efisiensi Kasir:
1. **Compact & High-Density:** Informasi produk, harga, dan keranjang disusun padat agar kasir dapat memproses transaksi dalam 1 layar tanpa banyak menggulir (*minimal scrolling*).
2. **Balanced Whitespace:** Kepadatan informasi diseimbangkan dengan padding modular (12px - 16px) dan jarak grid teratur (8px - 12px) sehingga tetap nyaman dipandang dan tidak berantakan (*uncluttered*).
3. **Split Two-Column Workspace:**
   - **Kolom Kiri (Katalog Produk - 60%):** Dilengkapi bilah pencarian cepat (pencarian instan nama, SKU, atau barcode), pil filter kategori yang dapat diklik langsung, dan grid produk responsif berketinggian ringkas.
   - **Kolom Kanan (Keranjang & Pembayaran - 40%):** Mengambang (*sticky drawer*) dengan daftar item pesanan yang memiliki kontrol kuantitas (+/-), ringkasan total belanja mencolok, opsi pembayaran multi-metode (Cash, QRIS, Transfer), tombol preset uang pas/pecahan cepat, serta kotak informasi kembalian otomatis.
4. **Alur Transaksi Cepat (Speed of Checkout):**
   - Kasir memilih produk atau memindai barcode.
   - Sistem langsung menambahkan ke keranjang dan mengalkulasi total.
   - Kasir memilih metode pembayaran atau menekan tombol uang cepat (misal: "Uang Pas", "50k", "100k").
   - Tombol utama "Proses Checkout" aktif seketika dengan kontras warna biru terpercaya `#1877F2`.

---

## 4. Component Stylings

### A. Kartu Produk (Product Card)
- **Latar Belakang:** `#FFFFFF` (Putih bersih).
- **Border:** `1px solid #E2E8F0`.
- **Sudut (Radius):** `10px`.
- **Bayangan:** `box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02)`.
- **Interaksi Hover:** Border berubah ke `#CBD5E1`, bayangan naik ke `0 4px 12px rgba(0, 0, 0, 0.05)`, sedikit terangkat (`transform: translateY(-1px)`).
- **Badge Kategori:** Pill lembut abu-abu dingin (`#F1F5F9`, teks `#475569`, font 11px).
- **Badge Stok:** Pill status stok (`#ECFDF5` teks `#047857` untuk stok aman; `#FFFBEB` teks `#B45309` untuk stok menipis).
- **Harga Produk:** Teks tebal `#0F172A` (15px) dengan angka tabel presisi.
- **Tombol Tambah:** Tombol biru terpercaya `#1877F2` yang ringkas dan mudah disentuh.

### B. Keranjang Belanja (Sticky Cart Drawer)
- **Posisi:** `position: sticky; top: 16px`.
- **Area Item:** Ketinggian terukur dengan scrollbar halus, setiap baris item memiliki judul tegas, stepper kuantitas (+ / -), tombol hapus cepat, dan subtotal yang jelas.
- **Card Total Belanja:** Latar belakang `#F8FAFC`, border `1px solid #E2E8F0`, dengan nominal total dalam ukuran besar dan warna aksen biru `#1877F2`.

### C. Pembayaran & Kembalian (Payment & Change)
- **Kartu Metode Bayar:** 3 kolom kompak untuk Cash, QRIS, dan Transfer. Saat nominal diisi, kartu mendapat status aktif dengan border `2px solid #1877F2` dan background `#F0F7FF`.
- **Preset Uang Cepat (Quick Cash):** Tombol pintasan praktis ("Uang Pas", "Rp 50.000", "Rp 100.000", "Rp 200.000") untuk pengisian nominal pembayaran tunai dalam 1 klik.
- **Kotak Kembalian:** Menggunakan aksen hijau emerald lembut (`#ECFDF5`, border `#A7F3D0`, teks `#065F46`, nilai kembalian tebal `#047857`) agar kasir dan pembeli mendapatkan konfirmasi visual yang tegas.

### D. Tombol Aksi Utama (Primary Call to Action)
- **Warna:** `#1877F2` (Trustworthy Blue).
- **Hover / Active:** `#166FE5` / `#0055D6`.
- **Teks:** Putih `#FFFFFF`, huruf tebal (`font-weight: 700`), ukuran 16px.
- **Tinggi:** Minimal 46px - 48px untuk target sentuh optimal pada layar POS touchscreen.
- **Bayangan:** `box-shadow: 0 2px 6px rgba(24, 119, 242, 0.25)`.

---

## 5. Typography & Readability
- **Font Utama:** Inter / System UI Font stack (`-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`).
- **Nominal Uang & Angka:** Dioptimalkan dengan *tabular figures* (`font-feature-settings: 'tnum'`) agar seluruh angka desimal dan digit uang sejajar sempurna secara vertikal.
- **Hierarki Skala:**
  - Grand Total: `22px - 26px`, Bold 800.
  - Judul Kartu & Section: `15px - 16px`, SemiBold 600.
  - Nama Produk: `13px - 14px`, SemiBold 600.
  - Harga & Subtotal: `14px - 15px`, Bold 700.
  - SKU / Barcode / Keterangan: `11px - 12px`, Regular 400.

---

## 6. Do's and Don'ts

- ✅ **Do:** Gunakan warna biru `#1877F2` secara selektif khusus untuk aksi utama (tombol Checkout, Tambah Item, dan status filter aktif) agar kontrasnya menjadi pemandu visual utama bagi kasir.
- ✅ **Do:** Gunakan warna putih bersih `#FFFFFF` pada kartu produk dan keranjang, dengan latar belakang abu-abu sejuk `#F4F6F8` untuk memancarkan kesan classy dan terpisah total dari antarmuka media sosial.
- ✅ **Do:** Pertahankan tata letak yang padat (*compact*) dengan informasi harga, SKU, dan stok yang langsung terbaca dalam sekilas pandang.
- ❌ **Don't:** Menggunakan warna biru pada sembarang elemen dekoratif yang tidak dapat diklik atau tidak memerlukan aksi kasir.
- ❌ **Don't:** Membuat kartu produk terlalu tinggi atau berjarak terlalu renggang yang memaksa kasir banyak melakukan scroll.
- ❌ **Don't:** Menghilangkan feedback visual saat item berhasil dimasukkan atau saat pembayaran telah mencapai nominal yang sesuai.
