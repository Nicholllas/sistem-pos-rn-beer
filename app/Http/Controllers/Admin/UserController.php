<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'name'  => $this->normalizeName($request->name),
            'email' => strtolower(trim($request->email)),
        ]);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9\s\.\-]+$/'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'role'      => ['required', Rule::in(['admin', 'cashier'])],
            'is_active' => ['nullable', 'boolean'],
        ], $this->validationMessages());

        User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => $validated['password'],
            'role'      => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->merge([
            'name'  => $this->normalizeName($request->name),
            'email' => strtolower(trim($request->email)),
        ]);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9\s\.\-]+$/'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => ['required', Rule::in(['admin', 'cashier'])],
            'is_active' => ['nullable', 'boolean'],
        ], $this->validationMessages());

        if ($user->id === Auth::id() && $validated['role'] !== 'admin') {
            return back()->withInput()->withErrors(['role' => 'Kamu tidak bisa mengubah role akun sendiri dari admin menjadi cashier.']);
        }

        if ($user->id === Auth::id() && ! $request->boolean('is_active')) {
            return back()->withInput()->withErrors(['is_active' => 'Kamu tidak bisa menonaktifkan akun sendiri.']);
        }

        if ($this->isLastActiveAdmin($user) && ($request->boolean('is_active') === false || $request->role !== 'admin')) {
            return back()->withInput()->withErrors(['is_active' => 'User ini adalah admin aktif terakhir. Tidak boleh dinonaktifkan atau diubah menjadi kasir.']);
        }

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diubah.');
    }

    public function destroy(User $user)
    {
        if ($this->isLastActiveAdmin($user)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['delete' => 'User ini adalah admin aktif terakhir. Tidak boleh dihapus.']);
        }

        if (auth()->id() === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['delete' => 'Kamu tidak bisa menghapus akun yang sedang digunakan.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $validated['password']]);

        return redirect()->route('admin.users.index')->with('success', 'Password user berhasil direset.');
    }

    private function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        return $value === '' ? null : mb_strtoupper($value);
    }

    private function validationMessages(): array
    {
        return [
            'name.regex'          => 'Nama hanya boleh berisi huruf, angka, spasi, titik, dan strip.',
            'password.confirmed'  => 'Konfirmasi password tidak sesuai.',
            'role.in'             => 'Role harus admin atau cashier.',
        ];
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== 'admin' || !$user->is_active) {
            return false;
        }

        return User::where('role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}