@extends('layouts.admin')

@section('title', 'Log Aktivitas Sistem')
@section('page-title', 'Log Aktivitas Sistem')
@section('page-subtitle', 'Jejak audit untuk seluruh perubahan sensitif, pergeseran stok, shift kasir, dan otorisasi supervisor.')

@section('content')
    @php
        $activeFilterCount = collect(['user_id', 'module', 'action', 'search', 'start_date', 'end_date'])
            ->filter(fn($key) => request()->filled($key))
            ->count();
    @endphp

    <div class="crud-page">
        {{-- Filter Panel --}}
        <section class="crud-panel logs-filter-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Filter Log Aktivitas</h2>
                    <p>Cari berdasarkan kata kunci, modul, pelaku aktivitas, atau rentang waktu.</p>
                </div>
                @if ($activeFilterCount > 0)
                    <span class="crud-count-badge">{{ $activeFilterCount }} filter aktif</span>
                @endif
            </div>

            <div class="crud-panel-body">
                <form action="{{ route('admin.activity-logs.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label for="search" class="form-label">Cari Deskripsi</label>
                            <input type="text" name="search" id="search" class="form-control crud-form-control"
                                placeholder="Contoh: INV-..., produk, void..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="user_id" class="form-label">User / Pelaku</label>
                            <select name="user_id" id="user_id" class="form-select crud-form-control">
                                <option value="">Semua User</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="module" class="form-label">Modul</label>
                            <select name="module" id="module" class="form-select crud-form-control">
                                <option value="">Semua Modul</option>
                                @foreach ($modules as $mod)
                                    <option value="{{ $mod }}" @selected(request('module') === $mod)>
                                        {{ $mod }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="start_date" class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" id="start_date" class="form-control crud-form-control"
                                value="{{ request('start_date') }}">
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="end_date" class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" id="end_date" class="form-control crud-form-control"
                                value="{{ request('end_date') }}">
                        </div>
                    </div>

                    <div class="crud-filter-actions">
                        @if ($activeFilterCount > 0)
                            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-outline-secondary">
                                Reset Filter
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>
        </section>

        {{-- Audit Trail Table --}}
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar Aktivitas Tercatat</h2>
                    <p>Catatan kronologis perubahan penting pada transaksi, stok, master data, dan shift kasir.</p>
                </div>
                <span class="crud-count-badge">{{ $logs->total() }} entri</span>
            </div>

            <div class="sales-result-summary">
                <span>
                    Menampilkan
                    <strong>{{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}</strong>
                    dari
                    <strong>{{ $logs->total() }}</strong>
                    aktivitas tercatat
                </span>
            </div>

            <div class="crud-panel-body is-flush">
                <div class="table-responsive">
                    <table class="table crud-table table-hover">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Modul &amp; Aksi</th>
                                <th>Deskripsi Aktivitas</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                @php
                                    $actionLower = strtolower($log->action);
                                    $statusClass = str_contains($actionLower, 'void') || str_contains($actionLower, 'delete')
                                        ? 'is-danger'
                                        : (str_contains($actionLower, 'close') ? 'is-warning' : 'is-info');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="crud-audit-cell">
                                            <strong>{{ $log->created_at->format('d/m/Y') }}</strong>
                                            <small>{{ $log->created_at->format('H:i:s') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="crud-audit-cell">
                                            <strong>{{ $log->user?->name ?? 'System' }}</strong>
                                            <small>{{ $log->user?->role ? strtoupper($log->user->role) : 'SYSTEM' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <span class="crud-neutral-badge">{{ $log->module }}</span>
                                            <span class="crud-status {{ $statusClass }}">{{ $log->action }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span>{{ $log->description }}</span>
                                    </td>
                                    <td>
                                        <span class="crud-neutral-badge">{{ $log->ip_address ?? '-' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Belum ada data aktivitas yang tercatat sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="crud-pagination">
                        <span>Halaman {{ $logs->currentPage() }} dari {{ $logs->lastPage() }}</span>
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
