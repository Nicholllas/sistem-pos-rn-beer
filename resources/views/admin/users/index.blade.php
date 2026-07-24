@extends('layouts.admin')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola akun, hak akses, status login, dan password pengguna.')

@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        Tambah User
    </a>
@endsection

@section('content')
    @php
        $authenticatedUserId = auth()->id();
    @endphp

    <div class="crud-page">
        <section class="crud-panel">
            <div class="crud-panel-header">
                <div>
                    <h2>Daftar User</h2>

                    <p>
                        Total {{ number_format($users->total(), 0, ',', '.') }}
                        akun terdaftar dalam sistem.
                    </p>
                </div>

                <span class="crud-count-badge">
                    {{ $users->total() }} user
                </span>
            </div>

            <div class="table-responsive">
                <table class="table crud-table user-crud-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat Pada</th>
                            <th>Terakhir Diubah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $isCurrentUser = $user->id === $authenticatedUserId;

                                $initials = collect(preg_split('/\s+/', trim($user->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                                    ->implode('');
                            @endphp

                            <tr>
                                <td>
                                    <div class="user-record-cell">
                                        <span class="user-record-avatar">
                                            {{ $initials ?: 'U' }}
                                        </span>

                                        <div class="user-record-info">
                                            <div class="user-record-name">
                                                <strong>{{ $user->name }}</strong>

                                                @if ($isCurrentUser)
                                                    <span class="user-self-badge">
                                                        Akun Saya
                                                    </span>
                                                @endif
                                            </div>

                                            <span>{{ $user->email }}</span>

                                            <small>
                                                ID #{{ $user->id }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="user-role-badge
                                            {{ $user->role === 'admin' ? 'is-admin' : 'is-cashier' }}">
                                        {{ $user->role === 'admin' ? 'Administrator' : 'Kasir' }}
                                    </span>
                                </td>

                                <td>
                                    <span
                                        class="crud-status
                                            {{ $user->is_active ? 'is-success' : 'is-muted' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $user->created_at?->format('d M Y') ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $user->created_at?->format('H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-audit-cell">
                                        <strong>
                                            {{ $user->updated_at?->format('d M Y') ?? '-' }}
                                        </strong>

                                        <small>
                                            {{ $user->updated_at?->format('H:i') ?? '-' }}
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="crud-actions">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                            data-reset-password-url="{{ route('admin.users.reset-password', $user) }}"
                                            data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}">
                                            Reset Password
                                        </button>

                                        @if (!$isCurrentUser)
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                class="js-confirm-action" data-confirm-icon="warning"
                                                data-confirm-title="Hapus user?"
                                                data-confirm-message="Akun {{ $user->name }} akan dihapus dan tidak dapat digunakan untuk login."
                                                data-confirm-button="Ya, Hapus" data-confirm-class="btn btn-danger px-4">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="user-protected-badge"
                                                title="Akun yang sedang digunakan tidak dapat dihapus">
                                                Dilindungi
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="crud-empty-state">
                                        <span class="crud-empty-avatar">
                                            U
                                        </span>

                                        <strong>Belum ada user</strong>

                                        <p>
                                            Tambahkan akun administrator atau kasir
                                            agar dapat mengakses sistem.
                                        </p>

                                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                                            Tambah User
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="crud-pagination">
                    <span>
                        Menampilkan {{ $users->firstItem() }}
                        sampai {{ $users->lastItem() }}
                        dari {{ $users->total() }} user
                    </span>

                    {{ $users->links() }}
                </div>
            @endif
        </section>
    </div>

    {{-- Satu modal digunakan untuk seluruh user --}}
    <div class="modal fade crud-modal" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="#" method="POST" class="modal-content js-submit-form" id="resetPasswordForm">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="resetPasswordModalLabel">
                            Reset Password
                        </h5>

                        <p>
                            Buat password baru untuk akun yang dipilih.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="user-reset-target">
                        <span class="user-record-avatar" id="resetUserAvatar">
                            U
                        </span>

                        <div>
                            <strong id="resetUserName">
                                User
                            </strong>

                            <small id="resetUserEmail">
                                -
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="resetPassword" class="form-label">
                            Password Baru
                            <span class="crud-required">*</span>
                        </label>

                        <div class="crud-password-field">
                            <input type="password" name="password" id="resetPassword" class="form-control crud-form-control"
                                required minlength="8" autocomplete="new-password">

                            <button type="button" class="crud-password-toggle" data-password-toggle="resetPassword">
                                Lihat
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="resetPasswordConfirmation" class="form-label">
                            Konfirmasi Password
                            <span class="crud-required">*</span>
                        </label>

                        <div class="crud-password-field">
                            <input type="password" name="password_confirmation" id="resetPasswordConfirmation"
                                class="form-control crud-form-control" required minlength="8" autocomplete="new-password"
                                data-password-confirmation-for="resetPassword"
                                data-password-match-target="resetPasswordMatch">

                            <button type="button" class="crud-password-toggle"
                                data-password-toggle="resetPasswordConfirmation">
                                Lihat
                            </button>
                        </div>

                        <small class="crud-password-match" id="resetPasswordMatch">
                            Minimal 8 karakter.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-primary js-submit-button"
                        data-loading-text="Menyimpan password...">
                        Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
