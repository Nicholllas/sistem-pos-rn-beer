<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRedirectLoopTest extends TestCase
{
    use DatabaseTransactions;

    public function test_session_configuration_uses_24h_lifetime_and_expire_on_close(): void
    {
        $this->assertSame(1440, config('session.lifetime'));
        $this->assertTrue((bool) config('session.expire_on_close'));
    }

    public function test_guest_visiting_root_redirects_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_visiting_root_or_login_redirect_directly_without_loop(): void
    {
        $admin = User::factory()->admin()->create();
        $supervisor = User::factory()->supervisor()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get('/login')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($supervisor)
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($cashier)
            ->get('/')
            ->assertRedirect(route('admin.sales.pos'));

        $this->actingAs($cashier)
            ->get('/login')
            ->assertRedirect(route('admin.sales.pos'));
    }

    public function test_inactive_user_with_stale_session_is_logged_out_cleanly_without_loop(): void
    {
        $inactive = User::factory()->cashier()->create([
            'is_active' => false,
        ]);

        $this->actingAs($inactive)
            ->get('/')
            ->assertRedirect(route('login'));

        $this->assertGuest();

        // Subsequent GET /login renders 200 OK instead of looping
        $this->get('/login')->assertOk();
    }

    public function test_new_login_revokes_previous_sessions_and_does_not_issue_remember_cookie(): void
    {
        config(['session.driver' => 'database']);

        $cashier = User::factory()->cashier()->create([
            'password' => Hash::make('secret123'),
            'remember_token' => 'old-remember-token',
        ]);

        DB::table('sessions')->insert([
            'id' => 'old-device-session-001',
            'user_id' => $cashier->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'OldDevice',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'secret123',
            'remember' => 'on',
        ]);

        $response->assertRedirect(route('admin.sales.pos'));
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());

        $this->assertDatabaseMissing('sessions', [
            'id' => 'old-device-session-001',
        ]);

        $this->assertNotSame('old-remember-token', $cashier->fresh()->remember_token);
    }

    public function test_admin_reset_password_role_change_and_deactivation_revoke_user_sessions(): void
    {
        config(['session.driver' => 'database']);

        $admin = User::factory()->admin()->create();
        $target = User::factory()->cashier()->create([
            'name' => 'KASIR TARGET',
        ]);

        $insertSession = function (string $id) use ($target): void {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $target->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'TestDevice',
                'payload' => base64_encode(serialize([])),
                'last_activity' => now()->timestamp,
            ]);
        };

        // 1. Reset password revokes existing sessions
        $insertSession('sess-before-reset');
        $this->actingAs($admin)
            ->put(route('admin.users.reset-password', $target), [
                'password' => 'newpass12345',
                'password_confirmation' => 'newpass12345',
            ])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('sessions', ['id' => 'sess-before-reset']);

        // 2. Role change revokes existing sessions
        $insertSession('sess-before-role');
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'KASIR TARGET',
                'email' => $target->email,
                'role' => 'supervisor',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('sessions', ['id' => 'sess-before-role']);

        // 3. Deactivation revokes existing sessions
        $insertSession('sess-before-deactivate');
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'KASIR TARGET',
                'email' => $target->email,
                'role' => 'supervisor',
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('sessions', ['id' => 'sess-before-deactivate']);
    }

    public function test_authenticate_session_kicks_user_when_password_changes(): void
    {
        $cashier = User::factory()->cashier()->create();

        // First request stores password hash in active session
        $this->actingAs($cashier)
            ->get(route('admin.sales.pos'))
            ->assertOk();

        // Simulate password changed elsewhere
        $cashier->update(['password' => Hash::make('changed-password-999')]);

        // Next request must be kicked to login and logged out
        $this->get(route('admin.sales.pos'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
