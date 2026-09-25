<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\AuditLog;
use App\Models\Finance;
use App\Models\FinanceCategory;
use App\Models\FinancePeriod;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\PengurusPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WhiteboxNewFeaturesTest extends TestCase
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

    protected function createApprovedUser(string $role, string $tingkatan = 'Bantara'): User
    {
        $user = User::create([
            'username' => strtolower($role).'_wb_'.uniqid(),
            'name' => ucfirst($role),
            'email' => strtolower($role).'_wb@test.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $user->id,
            'ambalan_id' => 1,
            'nta' => strtolower($role).'_nta_'.uniqid(),
            'nama_lengkap' => ucfirst($role),
            'kelas' => '-',
            'tingkatan' => $tingkatan,
            'angkatan' => '018',
            'nomor_urut' => $user->id,
            'nta_username' => strtolower($role).'_wb_'.uniqid(),
            'no_hp' => '-',
            'status_aktif' => 'Aktif',
        ]);

        return $user;
    }

    protected function createJuruUangUser(): User
    {
        $user = $this->createApprovedUser('Pengurus');
        $position = PengurusPosition::firstOrCreate(
            ['code' => 'juru_uang_putra'],
            ['name' => 'Juru Uang Putra', 'description' => 'Juru Uang', 'is_putra' => true]
        );
        MemberPosition::create([
            'member_id' => $user->member->id,
            'position_id' => $position->id,
            'assigned_by_user_id' => 1,
        ]);

        return $user;
    }

    public function test_audit_log_model_can_be_created(): void
    {
        $user = $this->createApprovedUser('Admin');
        $log = AuditLog::create([
            'actor_id' => $user->id,
            'action' => 'test.action',
            'entity_type' => 'App\Models\User',
            'entity_id' => $user->id,
            'metadata' => ['key' => 'value'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'actor_id' => $user->id,
            'action' => 'test.action',
            'entity_type' => 'App\Models\User',
            'entity_id' => $user->id,
        ]);
    }

    public function test_audit_log_actor_relationship(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $log = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'login',
            'entity_type' => 'App\Models\User',
            'entity_id' => $admin->id,
        ]);

        $this->assertInstanceOf(User::class, $log->actor);
        $this->assertEquals($admin->id, $log->actor->id);
    }

    public function test_audit_log_metadata_is_cast_to_array(): void
    {
        $log = AuditLog::create([
            'actor_id' => null,
            'action' => 'system.init',
            'entity_type' => 'App\Models\Config',
            'entity_id' => 1,
            'metadata' => ['version' => '1.0', 'status' => 'success'],
        ]);

        $this->assertIsArray($log->metadata);
        $this->assertEquals('1.0', $log->metadata['version']);
    }

    public function test_audit_log_index_renders_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'finance.created',
            'entity_type' => 'App\Models\Finance',
            'entity_id' => 1,
            'metadata' => ['nominal' => 100000],
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('AuditLogs/Index')
            && $page->has('logs')
            && $page->has('filters'));
    }

    public function test_audit_log_index_forbidden_for_non_admin(): void
    {
        $pemb = $this->createApprovedUser('Pembina');
        $this->actingAs($pemb)->get('/audit-logs')->assertForbidden();
    }

    public function test_audit_log_index_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->get('/audit-logs')->assertForbidden();
    }

    public function test_audit_log_index_forbidden_for_guest(): void
    {
        $this->get('/audit-logs')->assertRedirect('login');
    }

    public function test_audit_log_filter_by_action(): void
    {
        $admin = $this->createApprovedUser('Admin');
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'finance.created',
            'entity_type' => 'App\Models\Finance',
            'entity_id' => 1,
        ]);
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'finance.posted',
            'entity_type' => 'App\Models\Finance',
            'entity_id' => 2,
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs?action=finance');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data', 2));
    }

    public function test_audit_log_filter_by_entity_type(): void
    {
        $admin = $this->createApprovedUser('Admin');
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test',
            'entity_type' => 'App\Models\Finance',
            'entity_id' => 1,
        ]);
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test',
            'entity_type' => 'App\Models\Member',
            'entity_id' => 1,
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs?entity_type=Finance');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data', 1));
    }

    public function test_audit_log_filter_by_actor_id(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $otherUser = $this->createApprovedUser('Pembina');
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test',
            'entity_type' => 'App\Models\User',
            'entity_id' => $admin->id,
        ]);
        AuditLog::create([
            'actor_id' => $otherUser->id,
            'action' => 'test',
            'entity_type' => 'App\Models\User',
            'entity_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($admin)->get("/audit-logs?actor_id={$admin->id}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data', 1));
    }

    public function test_audit_log_filter_by_non_numeric_actor_id_ignored(): void
    {
        $admin = $this->createApprovedUser('Admin');
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test',
            'entity_type' => 'App\Models\User',
            'entity_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs?actor_id=abc');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data', 1));
    }

    public function test_audit_log_filter_by_date_range(): void
    {
        $admin = $this->createApprovedUser('Admin');
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test.old',
            'entity_type' => 'App\Models\User',
            'entity_id' => $admin->id,
            'created_at' => now()->subDays(10),
        ]);
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'test.new',
            'entity_type' => 'App\Models\User',
            'entity_id' => $admin->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs?date_from='.now()->subDays(5)->toDateString());
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data', 1));
    }

    public function test_finance_category_model(): void
    {
        $category = FinanceCategory::create([
            'ambalan_id' => 1,
            'nama' => 'Iuran Wajib',
            'jenis' => 'Masuk',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('finance_categories', [
            'id' => $category->id,
            'nama' => 'Iuran Wajib',
            'jenis' => 'Masuk',
            'is_active' => true,
        ]);
    }

    public function test_finance_category_casts_is_active_to_boolean(): void
    {
        $category = FinanceCategory::create([
            'ambalan_id' => 1,
            'nama' => 'Tabungan',
            'jenis' => 'Keluar',
            'is_active' => false,
        ]);

        $this->assertIsBool($category->is_active);
        $this->assertFalse($category->is_active);
    }

    public function test_finance_category_finances_relationship(): void
    {
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
        $this->assertCount(0, $category->finances);
    }

    public function test_finance_period_model(): void
    {
        $period = FinancePeriod::create([
            'ambalan_id' => 1,
            'nama' => 'Triwulan I',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
            'is_closed' => false,
        ]);

        $this->assertDatabaseHas('finance_periods', [
            'id' => $period->id,
            'nama' => 'Triwulan I',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
            'is_closed' => false,
        ]);
    }

    public function test_finance_period_casts_is_closed_to_boolean(): void
    {
        $period = FinancePeriod::create([
            'ambalan_id' => 1,
            'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
            'is_closed' => true,
        ]);

        $this->assertIsBool($period->is_closed);
        $this->assertTrue($period->is_closed);
    }

    public function test_finance_category_index_renders_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        FinanceCategory::create(['ambalan_id' => 1, 'nama' => 'Iuran Wajib', 'jenis' => 'Masuk', 'is_active' => true]);
        FinanceCategory::create(['ambalan_id' => 1, 'nama' => 'Tabungan', 'jenis' => 'Keluar', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/finance/categories');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/Categories')
            && $page->has('categories')
            && $page->has('ambalans'));
    }

    public function test_finance_category_index_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->get('/finance/categories')->assertForbidden();
    }

    public function test_finance_category_index_allows_juru_uang(): void
    {
        $juruUang = $this->createJuruUangUser();
        FinanceCategory::create(['ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true]);

        $response = $this->actingAs($juruUang)->get('/finance/categories');
        $response->assertOk();
    }

    public function test_finance_category_store_validates_required_fields(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $response = $this->actingAs($admin)->post('/finance/categories', [
            'nama' => '',
            'jenis' => 'Invalid',
        ]);
        $response->assertSessionHasErrors(['nama', 'jenis']);
    }

    public function test_finance_category_store_requires_valid_jenis(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $response = $this->actingAs($admin)->post('/finance/categories', [
            'nama' => 'Test Category',
            'jenis' => 'InvalidType',
        ]);
        $response->assertSessionHasErrors(['jenis']);
    }

    public function test_finance_category_store_for_admin_creates_record(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $response = $this->actingAs($admin)->post('/finance/categories', [
            'nama' => 'Uang Gedung',
            'jenis' => 'Masuk',
            'is_active' => true,
        ]);
        $response->assertRedirect(route('finance.categories.index'));
        $this->assertDatabaseHas('finance_categories', [
            'nama' => 'Uang Gedung', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
    }

    public function test_finance_category_store_for_juru_uang_creates_record(): void
    {
        $juruUang = $this->createJuruUangUser();
        $response = $this->actingAs($juruUang)->post('/finance/categories', [
            'nama' => 'Infaq',
            'jenis' => 'Masuk',
        ]);
        $response->assertRedirect(route('finance.categories.index'));
        $this->assertDatabaseHas('finance_categories', ['nama' => 'Infaq']);
    }

    public function test_finance_category_store_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->post('/finance/categories', [
            'nama' => 'Test', 'jenis' => 'Masuk',
        ])->assertForbidden();
    }

    public function test_finance_category_update_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Old Name', 'jenis' => 'Masuk', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/categories/{$category->id}", [
            'nama' => 'New Name', 'jenis' => 'Keluar', 'is_active' => false,
        ]);
        $response->assertRedirect(route('finance.categories.index'));
        $this->assertDatabaseHas('finance_categories', [
            'id' => $category->id, 'nama' => 'New Name', 'jenis' => 'Keluar', 'is_active' => false,
        ]);
    }

    public function test_finance_category_update_validates_fields(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/categories/{$category->id}", [
            'nama' => '', 'jenis' => 'invalid',
        ]);
        $response->assertSessionHasErrors(['nama', 'jenis']);
    }

    public function test_finance_category_update_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
        $this->actingAs($anggota)->patch("/finance/categories/{$category->id}", [
            'nama' => 'New', 'jenis' => 'Masuk',
        ])->assertForbidden();
    }

    public function test_finance_category_toggle_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/categories/{$category->id}/toggle");
        $response->assertRedirect(route('finance.categories.index'));
        $this->assertFalse($category->fresh()->is_active);

        $response = $this->actingAs($admin)->patch("/finance/categories/{$category->id}/toggle");
        $response->assertRedirect(route('finance.categories.index'));
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_finance_category_destroy_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete("/finance/categories/{$category->id}");
        $response->assertRedirect(route('finance.categories.index'));
        $this->assertDatabaseMissing('finance_categories', ['id' => $category->id]);
    }

    public function test_finance_category_destroy_forbidden_if_used_in_finances(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Iuran', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
        Finance::create([
            'ambalan_id' => 1,
            'member_id' => $admin->member->id,
            'category_id' => $category->id,
            'jenis_transaksi' => 'Masuk',
            'nominal' => 100000,
            'keterangan' => 'Test',
            'tgl_transaksi' => now()->toDateString(),
            'created_by' => $admin->id,
            'status' => 'Posted',
            'receipt_no' => 'TEST-001',
        ]);

        $response = $this->actingAs($admin)->delete("/finance/categories/{$category->id}");
        $response->assertStatus(422);
        $this->assertDatabaseHas('finance_categories', ['id' => $category->id]);
    }

    public function test_finance_category_destroy_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Test', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
        $this->actingAs($anggota)->delete("/finance/categories/{$category->id}")->assertForbidden();
    }

    public function test_finance_periods_index_renders_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => false,
        ]);

        $response = $this->actingAs($admin)->get('/finance/periods');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/Periods')
            && $page->has('periods')
            && $page->has('ambalans'));
    }

    public function test_finance_periods_index_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->get('/finance/periods')->assertForbidden();
    }

    public function test_finance_period_store_validates_dates(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $response = $this->actingAs($admin)->post('/finance/periods', [
            'nama' => 'Test',
            'starts_at' => '2024-03-31',
            'ends_at' => '2024-01-01',
        ]);
        $response->assertSessionHasErrors(['ends_at']);
    }

    public function test_finance_period_store_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $response = $this->actingAs($admin)->post('/finance/periods', [
            'nama' => 'Triwulan II',
            'starts_at' => '2024-04-01',
            'ends_at' => '2024-06-30',
        ]);
        $response->assertRedirect(route('finance.periods.index'));
        $this->assertDatabaseHas('finance_periods', [
            'nama' => 'Triwulan II', 'starts_at' => '2024-04-01', 'ends_at' => '2024-06-30', 'is_closed' => false,
        ]);
    }

    public function test_finance_period_store_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->post('/finance/periods', [
            'nama' => 'Test', 'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31',
        ])->assertForbidden();
    }

    public function test_finance_period_update_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => false,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/periods/{$period->id}", [
            'nama' => 'Q1 2024 Updated',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
        ]);
        $response->assertRedirect(route('finance.periods.index'));
        $this->assertEquals('Q1 2024 Updated', $period->fresh()->nama);
    }

    public function test_finance_period_update_forbidden_if_closed(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Closed Period',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/periods/{$period->id}", [
            'nama' => 'Try Update',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
        ]);
        $response->assertStatus(422);
    }

    public function test_finance_period_close_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => false,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/periods/{$period->id}/close");
        $response->assertRedirect(route('finance.periods.index'));
        $this->assertTrue($period->fresh()->is_closed);
    }

    public function test_finance_period_close_forbidden_if_already_closed(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/finance/periods/{$period->id}/close");
        $response->assertStatus(422);
    }

    public function test_finance_period_destroy_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => false,
        ]);

        $response = $this->actingAs($admin)->delete("/finance/periods/{$period->id}");
        $response->assertRedirect(route('finance.periods.index'));
        $this->assertDatabaseMissing('finance_periods', ['id' => $period->id]);
    }

    public function test_finance_period_destroy_forbidden_if_used_in_finances(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Q1 2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => false,
        ]);
        Finance::create([
            'ambalan_id' => 1,
            'member_id' => $admin->member->id,
            'period_id' => $period->id,
            'jenis_transaksi' => 'Masuk',
            'nominal' => 100000,
            'keterangan' => 'Test',
            'tgl_transaksi' => now()->toDateString(),
            'created_by' => $admin->id,
            'status' => 'Posted',
            'receipt_no' => 'TEST-001',
        ]);

        $response = $this->actingAs($admin)->delete("/finance/periods/{$period->id}");
        $response->assertStatus(422);
        $this->assertDatabaseHas('finance_periods', ['id' => $period->id]);
    }

    public function test_finance_period_store_validates_required_fields(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $response = $this->actingAs($admin)->post('/finance/periods', [
            'nama' => '', 'starts_at' => '', 'ends_at' => '',
        ]);
        $response->assertSessionHasErrors(['nama', 'starts_at', 'ends_at']);
    }

    public function test_finance_periods_index_renders_for_pembina(): void
    {
        $pembina = $this->createApprovedUser('Pembina');
        $response = $this->actingAs($pembina)->get('/finance/periods');
        $response->assertOk();
    }

    public function test_finance_periods_index_renders_for_juru_uang(): void
    {
        $juruUang = $this->createJuruUangUser();
        $response = $this->actingAs($juruUang)->get('/finance/periods');
        $response->assertOk();
    }

    public function test_inventory_movement_model(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1,
            'kode_barang' => 'INV-001',
            'nama_barang' => 'Bendera',
            'jenis' => 'Aset',
            'satuan' => 'Unit',
            'jumlah' => 10,
            'kondisi' => 'Baik',
            'status_pinjam' => 'Tersedia',
        ]);

        $movement = InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal',
            'jumlah' => 10,
            'actor_id' => $admin->id,
            'catatan' => 'Initial stock',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal',
            'jumlah' => 10,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_inventory_movement_actor_relationship(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-002',
            'nama_barang' => 'Seil', 'jenis' => 'Stok', 'satuan' => 'Rool',
            'jumlah' => 5, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);
        $movement = InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Penyesuaian', 'jumlah' => 2,
            'actor_id' => $admin->id, 'catatan' => 'Restock',
        ]);

        $this->assertInstanceOf(User::class, $movement->actor);
        $this->assertEquals($admin->id, $movement->actor->id);
    }

    public function test_inventory_movement_inventory_relationship(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-003',
            'nama_barang' => 'Kapur', 'jenis' => 'Stok', 'satuan' => 'Karung',
            'jumlah' => 20, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);
        $movement = InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal', 'jumlah' => 20,
            'actor_id' => $admin->id, 'catatan' => 'Initial',
        ]);

        $this->assertInstanceOf(Inventory::class, $movement->inventory);
        $this->assertEquals($inventory->id, $movement->inventory->id);
    }

    public function test_inventory_has_movements_relationship(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-004',
            'nama_barang' => 'Kayu', 'jenis' => 'Stok', 'satuan' => 'Batang',
            'jumlah' => 50, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal', 'jumlah' => 50,
            'actor_id' => $admin->id, 'catatan' => 'Initial',
        ]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Penyesuaian', 'jumlah' => -5,
            'actor_id' => $admin->id, 'catatan' => 'Adjustment',
        ]);

        $this->assertCount(2, $inventory->movements);
    }

    public function test_inventory_movements_index_renders_for_admin(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-MOV-01',
            'nama_barang' => 'Buku Tulis', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 100, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        $response = $this->actingAs($admin)->get("/inventory/{$inventory->id}/movements");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Inventory/Movements')
            && $page->has('inventory')
            && $page->has('movements'));
    }

    public function test_inventory_movements_index_for_pembina(): void
    {
        $pembina = $this->createApprovedUser('Pembina');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-MOV-02',
            'nama_barang' => 'Pensil', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 50, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        $response = $this->actingAs($pembina)->get("/inventory/{$inventory->id}/movements");
        $response->assertOk();
    }

    public function test_inventory_movements_index_for_pengurus(): void
    {
        $pengurus = $this->createApprovedUser('Pengurus');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-MOV-03',
            'nama_barang' => 'Map', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 30, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        $response = $this->actingAs($pengurus)->get("/inventory/{$inventory->id}/movements");
        $response->assertOk();
    }

    public function test_inventory_movements_index_forbidden_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-MOV-04',
            'nama_barang' => 'Spidol', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 20, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        $this->actingAs($anggota)->get("/inventory/{$inventory->id}/movements")->assertForbidden();
    }

    public function test_inventory_movements_index_requires_auth(): void
    {
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-MOV-05',
            'nama_barang' => 'Tutor', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 10, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        $this->get("/inventory/{$inventory->id}/movements")->assertRedirect('login');
    }

    public function test_inventory_movements_shows_movement_data(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-MOV-06',
            'nama_barang' => 'Gunting', 'jenis' => 'Aset', 'satuan' => 'Unit',
            'jumlah' => 5, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal', 'jumlah' => 5,
            'actor_id' => $admin->id, 'catatan' => 'Initial',
        ]);

        $response = $this->actingAs($admin)->get("/inventory/{$inventory->id}/movements");
        $response->assertInertia(fn ($page) => $page->has('movements', 1)
            && $page->where('movements.0.jenis', 'Saldo Awal')
        );
    }

    public function test_finances_index_renders_for_anggota(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $response = $this->actingAs($anggota)->get('/finance');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/Index'));
    }

    public function test_finances_index_renders_for_juru_uang(): void
    {
        $juruUang = $this->createJuruUangUser();
        $response = $this->actingAs($juruUang)->get('/finance');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/Index')
            && $page->has('canManage'));
    }

    public function test_anggota_can_submit_own_payment(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $response = $this->actingAs($anggota)->post('/finance', [
            'nominal' => 50000,
            'keterangan' => 'Iuran bulanan',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('finances', [
            'member_id' => $anggota->member->id,
            'jenis_transaksi' => 'Masuk',
            'nominal' => 50000,
            'status' => 'Posted',
        ]);
    }

    public function test_anggota_payment_validation_requires_nominal(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $response = $this->actingAs($anggota)->post('/finance', [
            'keterangan' => 'Test',
        ]);
        $response->assertSessionHasErrors(['nominal']);
    }

    public function test_anggota_payment_requires_min_amount(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $response = $this->actingAs($anggota)->post('/finance', [
            'nominal' => 0,
            'keterangan' => 'Test',
        ]);
        $response->assertSessionHasErrors(['nominal']);
    }

    public function test_non_juru_uang_pengurus_cannot_manage_finance(): void
    {
        $pengurus = $this->createApprovedUser('Pengurus');
        $response = $this->actingAs($pengurus)->post('/finance', [
            'ambalan_id' => 1,
            'member_id' => $pengurus->member->id,
            'jenis_transaksi' => 'Keluar',
            'nominal' => 10000,
            'keterangan' => 'Expense',
            'tgl_transaksi' => now()->toDateString(),
        ]);
        $response->assertForbidden();
    }
}
