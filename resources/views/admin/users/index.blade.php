@extends('layouts.admin')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                Daftar User
            </h3>
        @section('page-actions')
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                + Tambah User
            </a>
        @endsection
    </div>

    <div class="card-body">

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Terjadi kesalahan.</strong>

                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th class="text-center">Role</th>
                        <th class="text-center">Status</th>
                        <th>Dibuat</th>
                        <th style="width: 160px;" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="text-center">
                                {{ $users->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <strong>{{ $user->name }}</strong>

                                @if ($user->id === auth()->id())
                                    <span class="badge bg-info ms-1">
                                        Akun Saya
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $user->email }}
                            </td>

                            <td class="text-center">
                                @if ($user->role === 'admin')
                                    <span class="badge bg-primary">
                                        Admin
                                    </span>
                                @elseif ($user->role === 'cashier')
                                    <span class="badge bg-success">
                                        Cashier
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        {{ $user->role }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if ($user->is_active)
                                    <span class="badge bg-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td>
                                <div class="d-grid gap-1">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#resetPasswordModal{{ $user->id }}">
                                        Reset Password
                                    </button>

                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Belum ada user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{-- Reset Password Modal --}}
            @foreach ($users as $user)
                {{-- Reset Password Modal --}}
                <div class="modal fade" id="resetPasswordModal{{ $user->id }}" tabindex="-1"
                    aria-labelledby="resetPasswordModalLabel{{ $user->id }}" aria-hidden="true">

                    <div class="modal-dialog modal-dialog-centered">
                        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST"
                            class="modal-content">
                            @csrf
                            @method('PUT')

                            <div class="modal-header">
                                <h5 class="modal-title" id="resetPasswordModalLabel{{ $user->id }}">
                                    Reset Password User
                                </h5>

                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p class="mb-3">
                                    Reset password untuk:
                                    <br>
                                    <strong>{{ $user->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </p>

                                <div class="mb-3">
                                    <label for="password{{ $user->id }}" class="form-label">
                                        Password Baru
                                    </label>

                                    <input type="password" name="password" id="password{{ $user->id }}"
                                        class="form-control" required minlength="8">
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation{{ $user->id }}" class="form-label">
                                        Konfirmasi Password Baru
                                    </label>

                                    <input type="password" name="password_confirmation"
                                        id="password_confirmation{{ $user->id }}" class="form-control" required
                                        minlength="8">
                                </div>

                                <small class="text-muted">
                                    Minimal password 8 karakter.
                                </small>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Batal
                                </button>

                                <button type="submit" class="btn btn-primary">
                                    Simpan Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

    </div>

    @if ($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    @endif
</div>

@endsection
