<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'supervisor_pin', 'role', 'is_active'])]
#[Hidden(['password', 'supervisor_pin', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'supervisor_pin' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isSupervisorOrAdmin(): bool
    {
        return in_array($this->role, ['admin', 'supervisor'], true);
    }

    public function canAuthorizeCashier(): bool
    {
        return $this->isSupervisorOrAdmin();
    }

    public function canViewShiftTotals(): bool
    {
        return $this->isSupervisorOrAdmin();
    }

    /**
     * Resolve the landing route name for the authenticated user's role.
     */
    public function homeRouteName(): string
    {
        return $this->isCashier() ? 'admin.sales.pos' : 'admin.dashboard';
    }

    /**
     * Log out the active web guard and invalidate the current session cleanly.
     */
    public static function logoutSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Revoke active database sessions for this user and rotate the remember token.
     */
    public function revokeAllSessions(?string $exceptSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            $query = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->id);

            if ($exceptSessionId !== null) {
                $query->where('id', '!=', $exceptSessionId);
            }

            $query->delete();
        }

        $this->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }

    /**
     * Verify a supervisor authorization PIN against active admin or supervisor accounts.
     */
    public static function verifySupervisorPin(string $pin): ?self
    {
        $authorizers = static::query()
            ->whereIn('role', ['admin', 'supervisor'])
            ->where('is_active', true)
            ->whereNotNull('supervisor_pin')
            ->get();

        foreach ($authorizers as $authorizer) {
            if (Hash::check($pin, $authorizer->supervisor_pin)) {
                return $authorizer;
            }
        }

        return null;
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'sold_by');
    }

    public function cashierShifts()
    {
        return $this->hasMany(CashierShift::class, 'user_id');
    }

    public function createdProducts()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function updatedProducts()
    {
        return $this->hasMany(Product::class, 'updated_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
