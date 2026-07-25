<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Admin Dashboard') - {{ config('app.name', 'RNBeer POS') }}
    </title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    @php
        $authUser = auth()->user();

        $roleLabels = [
            'admin' => 'Administrator',
            'cashier' => 'Kasir',
        ];

        $userRole = $roleLabels[$authUser->role] ?? 'User';

        $homeRoute = $authUser->role === 'cashier' ? route('admin.sales.pos') : route('admin.dashboard');

        $userInitials = collect(explode(' ', trim($authUser->name)))
            ->filter()
            ->take(2)
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');

        $isDashboardActive = request()->routeIs('admin.dashboard');

        $isCategoryActive = request()->routeIs('admin.categories.*');
        $isProductActive = request()->routeIs('admin.products.*');
        $isSupplierActive = request()->routeIs('admin.suppliers.*');
        $isUserActive = request()->routeIs('admin.users.*');

        $isPosActive = request()->routeIs('admin.sales.pos');
        $isSalesHistoryActive = request()->routeIs('admin.sales.history', 'admin.sales.receipt');

        $isPurchaseCreateActive = request()->routeIs('admin.purchases.create');

        $isPurchaseHistoryActive = request()->routeIs('admin.purchases.index', 'admin.purchases.show');

        $isStockInActive = request()->routeIs('admin.inventory.stock-in*');

        $isAdjustmentActive = request()->routeIs('admin.inventory.adjustment*');

        $isLowStockActive = request()->routeIs('admin.inventory.low-stock');

        $isStockLogActive = request()->routeIs('admin.inventory.logs');

        $isBatchActive = request()->routeIs('admin.inventory.batches');

        $isFinanceAccountActive = request()->routeIs('admin.finance.accounts.*');

        $isFinanceCategoryActive = request()->routeIs('admin.finance.categories.*');

        $isFinanceTransactionActive = request()->routeIs('admin.finance.transactions.*');

        $isSalesReportActive = request()->routeIs('admin.reports.sales');

        $isInventoryReportActive = request()->routeIs('admin.reports.inventory');

        $isPurchaseReportActive = request()->routeIs('admin.reports.purchases');
    @endphp

    <div class="app-wrapper">

        {{-- =====================================================
             TOP NAVBAR
        ====================================================== --}}
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid px-3">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn app-sidebar-toggle" data-lte-toggle="sidebar"
                        aria-label="Buka atau tutup sidebar">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <a href="{{ $homeRoute }}" class="app-header-brand d-none d-sm-flex">
                        <span class="app-header-brand-name">
                            RNBeer
                        </span>

                        <span class="app-header-brand-module">
                            {{ $authUser->role === 'cashier' ? 'Kasir POS' : 'Admin Panel' }}
                        </span>
                    </a>
                </div>

                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <button type="button" class="btn app-user-menu" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span class="app-user-avatar">
                                {{ $userInitials ?: 'U' }}
                            </span>

                            <span class="app-user-info d-none d-sm-flex">
                                <strong>{{ $authUser->name }}</strong>
                                <small>{{ $userRole }}</small>
                            </span>

                            <span class="app-user-chevron">
                                ▾
                            </span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end app-user-dropdown">
                            <div class="app-user-dropdown-header">
                                <span class="app-user-avatar app-user-avatar-large">
                                    {{ $userInitials ?: 'U' }}
                                </span>

                                <div>
                                    <strong>{{ $authUser->name }}</strong>
                                    <small>{{ $authUser->email }}</small>
                                </div>
                            </div>

                            <div class="dropdown-divider"></div>

                            <a href="{{ $homeRoute }}" class="dropdown-item">
                                Halaman Utama
                            </a>

                            @if ($authUser->role === 'cashier')
                                <a href="{{ route('admin.sales.history') }}" class="dropdown-item">
                                    Riwayat Penjualan
                                </a>
                            @endif

                            <div class="dropdown-divider"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button type="submit" class="dropdown-item app-logout-button">
                                    Keluar dari Sistem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        {{-- =====================================================
             SIDEBAR
        ====================================================== --}}
        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ $homeRoute }}" class="brand-link">
                    <span class="brand-mark">
                        RN
                    </span>

                    <span class="brand-content">
                        <strong>RNBeer</strong>
                        <small>Point of Sale</small>
                    </span>
                </a>
            </div>

            <div class="sidebar-wrapper" data-sidebar-scroll>
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        <li class="nav-header">
                            UTAMA
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-link {{ $isDashboardActive ? 'active' : '' }}"
                                @if ($isDashboardActive) aria-current="page" @endif>
                                <span class="nav-link-marker"></span>
                                <p>Dashboard Penjualan</p>
                            </a>
                        </li>

                        @if ($authUser->role === 'admin')
                            <li class="nav-header">
                                MASTER DATA
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.categories.index') }}"
                                    class="nav-link {{ $isCategoryActive ? 'active' : '' }}"
                                    @if ($isCategoryActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Kategori</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.products.index') }}"
                                    class="nav-link {{ $isProductActive ? 'active' : '' }}"
                                    @if ($isProductActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Produk</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.suppliers.index') }}"
                                    class="nav-link {{ $isSupplierActive ? 'active' : '' }}"
                                    @if ($isSupplierActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Supplier</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.users.index') }}"
                                    class="nav-link {{ $isUserActive ? 'active' : '' }}"
                                    @if ($isUserActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Manajemen User</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-header">
                            TRANSAKSI
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.sales.pos') }}"
                                class="nav-link nav-link-primary {{ $isPosActive ? 'active' : '' }}"
                                @if ($isPosActive) aria-current="page" @endif>
                                <span class="nav-link-marker"></span>

                                <p>
                                    Penjualan / Kasir
                                </p>

                                <span class="nav-link-badge">
                                    POS
                                </span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.sales.history') }}"
                                class="nav-link {{ $isSalesHistoryActive ? 'active' : '' }}"
                                @if ($isSalesHistoryActive) aria-current="page" @endif>
                                <span class="nav-link-marker"></span>
                                <p>Riwayat Penjualan</p>
                            </a>
                        </li>

                        @if ($authUser->role === 'admin')
                            <li class="nav-item">
                                <a href="{{ route('admin.purchases.create') }}"
                                    class="nav-link {{ $isPurchaseCreateActive ? 'active' : '' }}"
                                    @if ($isPurchaseCreateActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Pembelian Stok</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.purchases.index') }}"
                                    class="nav-link {{ $isPurchaseHistoryActive ? 'active' : '' }}"
                                    @if ($isPurchaseHistoryActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Riwayat Pembelian Stok</p>
                                </a>
                            </li>

                            <li class="nav-header">
                                INVENTORY
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.stock-in') }}"
                                    class="nav-link {{ $isStockInActive ? 'active' : '' }}"
                                    @if ($isStockInActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Stok Masuk Manual</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.adjustment') }}"
                                    class="nav-link {{ $isAdjustmentActive ? 'active' : '' }}"
                                    @if ($isAdjustmentActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Penyesuaian Stok</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.low-stock') }}"
                                    class="nav-link {{ $isLowStockActive ? 'active' : '' }}"
                                    @if ($isLowStockActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Stok Menipis</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.logs') }}"
                                    class="nav-link {{ $isStockLogActive ? 'active' : '' }}"
                                    @if ($isStockLogActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Log Stok</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.inventory.batches') }}"
                                    class="nav-link {{ $isBatchActive ? 'active' : '' }}"
                                    @if ($isBatchActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Batch FIFO</p>
                                </a>
                            </li>

                            <li class="nav-header">
                                FINANCE
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.finance.transactions.index') }}"
                                    class="nav-link {{ $isFinanceTransactionActive ? 'active' : '' }}"
                                    @if ($isFinanceTransactionActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>

                                    <p>Cash Ledger</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.finance.accounts.index') }}"
                                    class="nav-link {{ $isFinanceAccountActive ? 'active' : '' }}"
                                    @if ($isFinanceAccountActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>

                                    <p>Akun Kas & Bank</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.finance.categories.index') }}"
                                    class="nav-link {{ $isFinanceCategoryActive ? 'active' : '' }}"
                                    @if ($isFinanceCategoryActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>

                                    <p>Kategori Finance</p>
                                </a>
                            </li>

                            <li class="nav-header">
                                LAPORAN
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.reports.sales') }}"
                                    class="nav-link {{ $isSalesReportActive ? 'active' : '' }}"
                                    @if ($isSalesReportActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Laporan Penjualan</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.reports.inventory') }}"
                                    class="nav-link {{ $isInventoryReportActive ? 'active' : '' }}"
                                    @if ($isInventoryReportActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Laporan Stok</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.reports.purchases') }}"
                                    class="nav-link {{ $isPurchaseReportActive ? 'active' : '' }}"
                                    @if ($isPurchaseReportActive) aria-current="page" @endif>
                                    <span class="nav-link-marker"></span>
                                    <p>Laporan Pembelian Stok</p>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>

            <div class="sidebar-footer">
                <span class="sidebar-footer-status"></span>

                <div>
                    <strong>Sistem aktif</strong>
                    <small>RNBeer POS</small>
                </div>
            </div>
        </aside>

        {{-- =====================================================
             MAIN CONTENT
        ====================================================== --}}
        <main class="app-main" id="main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="page-heading">
                        <div>
                            <h1>
                                @yield('page-title', 'Dashboard')
                            </h1>

                            @hasSection('page-subtitle')
                                <p>
                                    @yield('page-subtitle')
                                </p>
                            @endif
                        </div>

                        @hasSection('page-actions')
                            <div class="page-heading-actions">
                                @yield('page-actions')
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @if (session('success'))
                        <div class="alert app-alert app-alert-success alert-dismissible fade show" role="alert">
                            <div class="app-alert-indicator"></div>

                            <div class="app-alert-content">
                                <strong>Berhasil</strong>
                                <span>{{ session('success') }}</span>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Tutup"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert app-alert app-alert-danger alert-dismissible fade show" role="alert">
                            <div class="app-alert-indicator"></div>

                            <div class="app-alert-content">
                                <strong>Periksa kembali input</strong>

                                <span>
                                    Ada data yang belum sesuai.
                                </span>

                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Tutup"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>

        {{-- =====================================================
             FOOTER
        ====================================================== --}}
        <footer class="app-footer">
            <span>
                &copy; {{ now()->year }} RNBeer POS
            </span>

            <span class="d-none d-sm-inline">
                {{ $authUser->role === 'cashier' ? 'Kasir POS' : 'Dashboard Administrator' }}
            </span>
        </footer>
    </div>

    @stack('scripts')
</body>

</html>
