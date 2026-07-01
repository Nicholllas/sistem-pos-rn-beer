<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'Beer POS') }}</title>

    {{-- favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    @php
        $authUser = auth()->user();

        $roleLabels = [
            'admin' => 'Admin',
            'cashier' => 'Kasir',
        ];

        $userRole = $roleLabels[$authUser->role] ?? 'User';

        $homeRoute = $authUser->role === 'cashier' ? route('admin.sales.pos') : route('admin.dashboard');
    @endphp

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <span class="navbar-toggler-icon"></span>
                        </a>
                    </li>

                    <li class="nav-item d-none d-md-block">
                        <a href="{{ $homeRoute }}" class="nav-link">
                            {{ $authUser->role === 'cashier' ? 'Kasir POS' : 'Dashboard Admin' }}
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <div class="nav-link py-0">
                            <div class="d-flex flex-column text-end">
                                <span class="fw-semibold">
                                    {{ $authUser->name }}
                                </span>

                                <small class="text-muted">
                                    {{ $userRole }}
                                </small>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ $homeRoute }}" class="brand-link">
                    <span class="brand-text fw-light">RNBeer POS</span>
                </a>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

                        <li class="nav-header">DASHBOARD</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <p>Dashboard Penjualan</p>
                            </a>
                        </li>

                        @if ($authUser->role === 'admin')
                            <li class="nav-header">MASTER DATA</li>

                            <li class="nav-item">
                                <a href="{{ route('admin.categories.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                    <p>Kategori</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.products.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                    <p>Produk</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.suppliers.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                                    <p>Supplier</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.users.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                    <p>Manajemen User</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-header">TRANSAKSI</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.sales.pos') }}"
                                class="nav-link {{ request()->routeIs('admin.sales.pos') ? 'active' : '' }}">
                                <p>Penjualan / Kasir</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.sales.history') }}"
                                class="nav-link {{ request()->routeIs('admin.sales.history') ? 'active' : '' }}">
                                <p>Riwayat Penjualan</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#"
                                class="nav-link {{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}">
                                <p>Pembelian Stok</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#"
                                class="nav-link {{ request()->routeIs('admin.purchases.history') ? 'active' : '' }}">
                                <p>Riwayat Pembelian</p>
                            </a>
                        </li>

                        <li class="nav-header">INVENTORY</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.inventory.stock-in') }}"
                                class="nav-link {{ request()->routeIs('admin.inventory.stock-in*') ? 'active' : '' }}">
                                <p>Stok Masuk Manual</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.inventory.adjustment') }}"
                                class="nav-link {{ request()->routeIs('admin.inventory.adjustment*') ? 'active' : '' }}">
                                <p>Penyesuaian Stok</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.inventory.low-stock') }}"
                                class="nav-link {{ request()->routeIs('admin.inventory.low-stock') ? 'active' : '' }}">
                                <p>Stok Menipis</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.inventory.logs') }}"
                                class="nav-link {{ request()->routeIs('admin.inventory.logs') ? 'active' : '' }}">
                                <p>Log Stok</p>
                            </a>
                        </li>

                        <li class="nav-header">LAPORAN</li>

                        <li class="nav-item">
                            <a href="{{ route('admin.reports.sales') }}"
                                class="nav-link {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
                                <p>Laporan Penjualan</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.reports.inventory') }}"
                                class="nav-link {{ request()->routeIs('admin.reports.inventory') ? 'active' : '' }}">
                                <p>Laporan Stok</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#"
                                class="nav-link {{ request()->routeIs('admin.reports.purchases') ? 'active' : '' }}">
                                <p>Laporan Pembelian</p>
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">
                                @yield('page-title', 'Dashboard')
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}

                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Ada input yang belum sesuai. Silakan cek kembali.

                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <strong>RNBeer POS</strong>

            <span class="float-end d-none d-sm-inline">
                {{ $authUser->role === 'cashier' ? 'Kasir POS' : 'Dashboard Admin' }}
            </span>
        </footer>
    </div>

    @stack('scripts')
</body>

</html>

