<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BlackboxWhiteboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Ambalan::create(['kode' => 'TEST', 'nama' => 'Test Ambalan']);
        Angkatan::create([
            'angkatan' => '018', 'nomor' => 18,
            'nama' => 'Angkatan 18', 'is_active' => true, 'is_current' => true,
        ]);
    }

    protected function createApprovedUser(string $role): User
    {
        $user = User::create([
            'username' => strtolower($role) . '_user_' . uniqid(),
            'name' => ucfirst($role),
            'email' => strtolower($role) . '@test.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
        ]);
        Member::create([
            'user_id' => $user->id,
            'ambalan_id' => 1,
            'nta' => strtolower($role) . '_nta_' . uniqid(),
            'nama_lengkap' => ucfirst($role),
            'kelas' => '-',
            'tingkatan' => 'Bantara',
            'angkatan' => '018',
            'nomor_urut' => $user->id,
            'nta_username' => strtolower($role) . '_user_' . uniqid(),
            'no_hp' => '-',
            'status_aktif' => 'Aktif',
        ]);
        return $user;
    }

    public function test_guest_can_access_home_page(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Guest/Home'));
    }

    public function test_guest_can_access_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_guest_can_access_registration_page(): void
    {
        $this->get('/login?isRegistration=1')->assertOk();
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('login');
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = $this->createApprovedUser('Admin');
        $response = $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect('dashboard');
    }

    public function test_login_with_username(): void
    {
        $user = $this->createApprovedUser('Admin');
        $response = $this->post('/login', [
            'identity' => $user->username,
            'password' => 'password123',
        ]);
        $response->assertRedirect('dashboard');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'identity' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors('identity');
    }

    public function test_login_fails_with_missing_fields(): void
    {
        $response = $this->post('/login', []);
        $response->assertSessionHasErrors(['identity', 'password']);
    }

    public function test_pending_user_redirects_to_pending_approval(): void
    {
        $user = User::create([
            'username' => 'pending_u_' . uniqid(),
            'name' => 'Pending',
            'email' => 'pending@test.com',
            'password' => Hash::make('password123'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'pending',
        ]);

        $response = $this->post('/login', [
            'identity' => 'pending@test.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect('pending-approval');
    }

    public function test_rejected_user_login_fails(): void
    {
        $user = User::create([
            'username' => 'rejected_u_' . uniqid(),
            'name' => 'Rejected',
            'email' => 'rejected@test.com',
            'password' => Hash::make('password123'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'rejected',
        ]);

        $response = $this->post('/login', [
            'identity' => 'rejected@test.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('identity');
    }

    public function test_registration_creates_pending_user(): void
    {
        $response = $this->post('/register', [
            'nama_lengkap' => 'Test User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('login');
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@test.com',
            'status' => 'pending',
            'role' => 'Anggota',
        ]);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->post('/register', [
            'nama_lengkap' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::create([
            'username' => 'exists_u_' . uniqid(),
            'name' => 'Existing',
            'email' => 'existing@test.com',
            'password' => Hash::make('password123'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
        ]);

        $response = $this->post('/register', [
            'nama_lengkap' => 'Test User',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'nama_lengkap' => 'Test User',
            'email' => 'newuser2@test.com',
            'password' => 'password123',
            'password_confirmation' => 'wrong',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_registration_rejects_short_password(): void
    {
        $response = $this->post('/register', [
            'nama_lengkap' => 'Test User',
            'email' => 'newuser3@test.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_password_is_hashed_after_registration(): void
    {
        $this->post('/register', [
            'nama_lengkap' => 'Hashed User',
            'email' => 'hashed@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'hashed@test.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotEquals('password123', $user->password);
    }

    public function test_new_user_gets_auto_generated_username(): void
    {
        $this->post('/register', [
            'nama_lengkap' => 'Auto User',
            'email' => 'auto@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'auto@test.com')->first();
        $prefix = (string) config('app.gudep_prefix', '31082008');
        $this->assertStringStartsWith($prefix . '.018.', $user->username);
    }

    public function test_member_record_created_on_registration(): void
    {
        $this->post('/register', [
            'nama_lengkap' => 'Member User',
            'email' => 'member@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'member@test.com')->first();
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'ambalan_id' => 1,
        ]);
    }

    public function test_dashboard_denied_for_pending_user(): void
    {
        $user = User::create([
            'username' => 'pending_d_' . uniqid(),
            'name' => 'Pending',
            'email' => 'pending2@test.com',
            'password' => Hash::make('password123'),
            'role' => 'Admin',
            'is_active' => true,
            'status' => 'pending',
        ]);

        $this->actingAs($user);
        $this->get('/dashboard')->assertRedirect('pending-approval');
    }

    public function test_dashboard_accessible_for_approved_admin(): void
    {
        $user = $this->createApprovedUser('Admin');
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_accessible_for_approved_anggota(): void
    {
        $user = $this->createApprovedUser('Anggota');
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_accessible_for_approved_pembina(): void
    {
        $user = $this->createApprovedUser('Pembina');
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_accessible_for_approved_pengurus(): void
    {
        $user = $this->createApprovedUser('Pengurus');
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_logout_clears_session(): void
    {
        $user = $this->createApprovedUser('Admin');
        $this->actingAs($user);
        $this->post('/logout')->assertRedirect('home');
        $this->assertGuest();
    }

    public function test_login_route_has_rate_limiting(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'identity' => 'rate@test.com',
                'password' => 'wrong',
            ]);
        }

        $response = $this->post('/login', [
            'identity' => 'rate@test.com',
            'password' => 'wrong',
        ]);
        $response->assertStatus(429);
    }

    public function test_registration_route_has_rate_limiting(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->post('/register', [
                'nama_lengkap' => 'Rate User',
                'email' => "rate{$i}@test.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $response = $this->post('/register', [
            'nama_lengkap' => 'Rate User',
            'email' => 'rate5@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(429);
    }

    public function test_pending_approval_page_accessible_for_pending_users(): void
    {
        $user = User::create([
            'username' => 'pend_page_' . uniqid(),
            'name' => 'Pending',
            'email' => 'pendpage@test.com',
            'password' => Hash::make('password123'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->get('/pending-approval')->assertOk();
    }

    public function test_login_with_remember_cookie(): void
    {
        $user = $this->createApprovedUser('Admin');
        $response = $this->post('/login', [
            'identity' => $user->email,
            'password' => 'password123',
            'remember' => '1',
        ]);
        $response->assertRedirect('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_already_authenticated_user_redirected_from_login(): void
    {
        $user = $this->createApprovedUser('Admin');
        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('dashboard');
    }

    public function test_no_duplicate_route_names(): void
    {
        $routes = app('router')->getRoutes();
        $names = [];
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name) {
                if (isset($names[$name])) {
                    $this->fail("Duplicate route name: {$name}");
                }
                $names[$name] = true;
            }
        }
        $this->assertTrue(true);
    }
}