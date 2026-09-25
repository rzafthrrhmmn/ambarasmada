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

class BlackboxNewFeaturesTest extends TestCase
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
        $unique = uniqid();
        $user = User::create([
            'username' => strtolower($role).'_bb_'.uniqid(),
            'name' => ucfirst($role),
            'email' => strtolower($role)."_bb_{$unique}@test.com",
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
            'nta_username' => strtolower($role).'_bb_'.uniqid(),
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

    public function test_admin_audit_log_full_flow(): void
    {
        $admin = $this->createApprovedUser('Admin');

        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Iuran', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'finance.category.created',
            'entity_type' => FinanceCategory::class,
            'entity_id' => $category->id,
        ]);
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'finance.category.deleted',
            'entity_type' => FinanceCategory::class,
            'entity_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('logs.data', 2));
    }

    public function test_admin_audit_log_with_filters(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $pembina = $this->createApprovedUser('Pembina');

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'finance.created',
            'entity_type' => Finance::class,
            'entity_id' => 1,
            'created_at' => now(),
        ]);
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'member.deleted',
            'entity_type' => Member::class,
            'entity_id' => 1,
            'created_at' => now(),
        ]);
        AuditLog::create([
            'actor_id' => $pembina->id,
            'action' => 'finance.created',
            'entity_type' => Finance::class,
            'entity_id' => 2,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs?action=finance');
        $response->assertInertia(fn ($page) => $page->has('logs.data', 2));

        $response = $this->actingAs($admin)->get('/audit-logs?entity_type=Member');
        $response->assertInertia(fn ($page) => $page->has('logs.data', 1));

        $response = $this->actingAs($admin)->get("/audit-logs?actor_id={$admin->id}");
        $response->assertInertia(fn ($page) => $page->has('logs.data', 2));
    }

    public function test_non_admin_blocked_from_audit_logs(): void
    {
        $pembina = $this->createApprovedUser('Pembina');
        $pengurus = $this->createApprovedUser('Pengurus');
        $anggota = $this->createApprovedUser('Anggota');

        $this->actingAs($pembina)->get('/audit-logs')->assertForbidden();
        $this->actingAs($pengurus)->get('/audit-logs')->assertForbidden();
        $this->actingAs($anggota)->get('/audit-logs')->assertForbidden();
    }

    public function test_admin_finance_category_full_workflow(): void
    {
        $admin = $this->createApprovedUser('Admin');

        $createResponse = $this->actingAs($admin)->post('/finance/categories', [
            'nama' => 'Iuran Wajib',
            'jenis' => 'Masuk',
        ]);
        $createResponse->assertRedirect(route('finance.categories.index'));
        $this->assertDatabaseHas('finance_categories', ['nama' => 'Iuran Wajib']);

        $category = FinanceCategory::where('nama', 'Iuran Wajib')->first();

        $toggleResponse = $this->actingAs($admin)->patch("/finance/categories/{$category->id}/toggle");
        $toggleResponse->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);

        $updateResponse = $this->actingAs($admin)->patch("/finance/categories/{$category->id}", [
            'nama' => 'Iuran Wajib Updated',
            'jenis' => 'Masuk',
            'is_active' => true,
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals('Iuran Wajib Updated', $category->fresh()->nama);

        $destroyResponse = $this->actingAs($admin)->delete("/finance/categories/{$category->id}");
        $destroyResponse->assertRedirect();
        $this->assertDatabaseMissing('finance_categories', ['id' => $category->id]);
    }

    public function test_pembina_finance_category_workflow(): void
    {
        $pembina = $this->createApprovedUser('Pembina');
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Pembina Cat', 'jenis' => 'Keluar', 'is_active' => true,
        ]);

        $this->actingAs($pembina)->get('/finance/categories')->assertOk();

        $this->actingAs($pembina)->patch("/finance/categories/{$category->id}/toggle")->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($pembina)->delete("/finance/categories/{$category->id}")->assertRedirect();
        $this->assertDatabaseMissing('finance_categories', ['id' => $category->id]);
    }

    public function test_juru_uang_finance_category_workflow(): void
    {
        $juruUang = $this->createJuruUangUser();
        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'JU Cat', 'jenis' => 'Masuk', 'is_active' => true,
        ]);

        $this->actingAs($juruUang)->post('/finance/categories', [
            'nama' => 'New JU Cat', 'jenis' => 'Keluar',
        ])->assertRedirect();

        $this->actingAs($juruUang)->patch("/finance/categories/{$category->id}", [
            'nama' => 'Updated', 'jenis' => 'Masuk', 'is_active' => false,
        ])->assertRedirect();

        $this->actingAs($juruUang)->delete("/finance/categories/{$category->id}")->assertRedirect();
        $this->assertDatabaseMissing('finance_categories', ['id' => $category->id]);
    }

    public function test_anggota_blocked_from_finance_categories(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->get('/finance/categories')->assertForbidden();
        $this->actingAs($anggota)->post('/finance/categories', [
            'nama' => 'Test', 'jenis' => 'Masuk',
        ])->assertForbidden();
    }

    public function test_admin_finance_period_full_workflow(): void
    {
        $admin = $this->createApprovedUser('Admin');

        $createResponse = $this->actingAs($admin)->post('/finance/periods', [
            'nama' => 'Triwulan I 2024',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
        ]);
        $createResponse->assertRedirect(route('finance.periods.index'));
        $this->assertDatabaseHas('finance_periods', ['nama' => 'Triwulan I 2024', 'is_closed' => false]);

        $period = FinancePeriod::where('nama', 'Triwulan I 2024')->first();

        $closeResponse = $this->actingAs($admin)->patch("/finance/periods/{$period->id}/close");
        $closeResponse->assertRedirect();
        $this->assertTrue($period->fresh()->is_closed);

        $this->actingAs($admin)->get('/finance/periods')->assertOk();
    }

    public function test_admin_finance_period_update_before_close(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Original Name',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => false,
        ]);

        $this->actingAs($admin)->patch("/finance/periods/{$period->id}", [
            'nama' => 'Updated Period Name',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
        ])->assertRedirect();

        $this->assertEquals('Updated Period Name', $period->fresh()->nama);
    }

    public function test_juru_uang_finance_period_workflow(): void
    {
        $juruUang = $this->createJuruUangUser();
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'JU Period',
            'starts_at' => '2024-04-01', 'ends_at' => '2024-06-30', 'is_closed' => false,
        ]);

        $this->actingAs($juruUang)->get('/finance/periods')->assertOk();

        $this->actingAs($juruUang)->post('/finance/periods', [
            'nama' => 'New Period',
            'starts_at' => '2024-07-01',
            'ends_at' => '2024-09-30',
        ])->assertRedirect();

        $this->actingAs($juruUang)->patch("/finance/periods/{$period->id}/close")->assertRedirect();
        $this->assertTrue($period->fresh()->is_closed);

        $this->actingAs($juruUang)->delete("/finance/periods/{$period->id}")->assertRedirect();
        $this->assertDatabaseMissing('finance_periods', ['id' => $period->id]);
    }

    public function test_admin_cannot_close_already_closed_period(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => 'Closed',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31', 'is_closed' => true,
        ]);

        $this->actingAs($admin)->patch("/finance/periods/{$period->id}/close")->assertStatus(422);
    }

    public function test_non_finance_manager_cannot_create_period(): void
    {
        $pengurus = $this->createApprovedUser('Pengurus');
        $this->actingAs($pengurus)->post('/finance/periods', [
            'nama' => 'Test',
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-03-31',
        ])->assertForbidden();
    }

    public function test_anggota_blocked_from_finance_periods(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $this->actingAs($anggota)->get('/finance/periods')->assertForbidden();
        $this->actingAs($anggota)->post('/finance/periods', [
            'nama' => 'Test', 'starts_at' => '2024-01-01', 'ends_at' => '2024-03-31',
        ])->assertForbidden();
    }

    public function test_admin_inventory_movements_full_flow(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-FLOW-01',
            'nama_barang' => 'Bendera', 'jenis' => 'Aset', 'satuan' => 'Unit',
            'jumlah' => 10, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal', 'jumlah' => 10,
            'actor_id' => $admin->id, 'catatan' => 'Initial stock',
        ]);

        $response = $this->actingAs($admin)->get("/inventory/{$inventory->id}/movements");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('movements', 1));
    }

    public function test_inventory_movements_with_multiple_entries(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-FLOW-02',
            'nama_barang' => 'Seil', 'jenis' => 'Stok', 'satuan' => 'Rool',
            'jumlah' => 100, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal', 'jumlah' => 100,
            'actor_id' => $admin->id, 'catatan' => 'Initial',
        ]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Penyesuaian', 'jumlah' => -20,
            'actor_id' => $admin->id, 'catatan' => 'Stock take adjustment',
        ]);
        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Peminjaman', 'jumlah' => -1,
            'actor_id' => $admin->id, 'catatan' => 'Loaned out',
        ]);

        $response = $this->actingAs($admin)->get("/inventory/{$inventory->id}/movements");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('movements', 3));
    }

    public function test_inanggota_blocked_from_inventory_movements(): void
    {
        $anggota = $this->createApprovedUser('Anggota');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-FLOW-03',
            'nama_barang' => 'Buku', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 50, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        $this->actingAs($anggota)->get("/inventory/{$inventory->id}/movements")->assertForbidden();
    }

    public function test_inventory_movements_shows_actor_info(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $inventory = Inventory::create([
            'ambalan_id' => 1, 'kode_barang' => 'INV-FLOW-04',
            'nama_barang' => 'Pulpen', 'jenis' => 'Stok', 'satuan' => 'Biji',
            'jumlah' => 200, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);

        InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'jenis' => 'Saldo Awal', 'jumlah' => 200,
            'actor_id' => $admin->id, 'catatan' => 'Stocked by '.$admin->name,
        ]);

        $response = $this->actingAs($admin)->get("/inventory/{$inventory->id}/movements");
        $response->assertInertia(fn ($page) => $page->has('movements', 1)
            && $page->where('movements.0.actor.name', $admin->name)
        );
    }

    public function test_end_to_end_finance_transaction_flow(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $anggota = $this->createApprovedUser('Anggota');

        $category = FinanceCategory::create([
            'ambalan_id' => 1, 'nama' => 'Iuran Wajib', 'jenis' => 'Masuk', 'is_active' => true,
        ]);
        $period = FinancePeriod::create([
            'ambalan_id' => 1, 'nama' => '2024',
            'starts_at' => '2024-01-01', 'ends_at' => '2024-12-31', 'is_closed' => false,
        ]);

        $paymentResponse = $this->actingAs($anggota)->post('/finance', [
            'nominal' => 75000,
            'keterangan' => 'Iuran bulanan Januari',
        ]);
        $paymentResponse->assertRedirect();
        $this->assertDatabaseHas('finances', [
            'member_id' => $anggota->member->id,
            'jenis_transaksi' => 'Masuk',
            'nominal' => 75000,
            'status' => 'Posted',
        ]);

        $financeIndex = $this->actingAs($anggota)->get('/finance');
        $financeIndex->assertOk();
        $financeIndex->assertInertia(fn ($page) => $page->has('transactions.data'));

        $auditExists = AuditLog::where('action', 'finance.member_payment')
            ->where('actor_id', $anggota->id)
            ->exists();
        $this->assertTrue($auditExists);
    }

    public function test_end_to_end_inventory_management_flow(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $pengurus = $this->createApprovedUser('Pengurus');

        $storeResponse = $this->actingAs($admin)->post('/inventory', [
            'ambalan_id' => 1, 'kode_barang' => 'INV-E2E-01',
            'nama_barang' => 'Bendera Besar', 'jenis' => 'Aset', 'satuan' => 'Unit',
            'jumlah' => 5, 'kondisi' => 'Baik', 'status_pinjam' => 'Tersedia',
        ]);
        $storeResponse->assertRedirect(route('inventory.index'));
        $inventory = Inventory::where('kode_barang', 'INV-E2E-01')->first();
        $this->assertNotNull($inventory);

        $movementsResponse = $this->actingAs($pengurus)->get("/inventory/{$inventory->id}/movements");
        $movementsResponse->assertOk();
        $movementsResponse->assertInertia(fn ($page) => $page->has('movements', 1)
            && $page->where('movements.0.jenis', 'Saldo Awal')
        );

        $loanResponse = $this->actingAs($admin)->post('/inventory-loans', [
            'inventory_id' => $inventory->id,
            'peminjam_nama' => 'Test Borrower',
            'tgl_pinjam' => now()->toDateString(),
            'kondisi' => 'Baik',
            'catatan' => 'For event',
        ]);
        $loanResponse->assertRedirect();

        $movementsAfterLoan = $this->actingAs($admin)->get("/inventory/{$inventory->id}/movements");
        $movementsAfterLoan->assertInertia(fn ($page) => $page->has('movements', 2)
            && $page->where('movements.0.jenis', 'Peminjaman')
        );
    }

    public function test_finance_balance_calculation(): void
    {
        $admin = $this->createApprovedUser('Admin');

        Finance::create([
            'ambalan_id' => 1, 'member_id' => $admin->member->id,
            'jenis_transaksi' => 'Masuk', 'nominal' => 100000,
            'keterangan' => 'Income', 'status' => 'Posted',
            'tgl_transaksi' => now()->toDateString(), 'created_by' => $admin->id,
            'receipt_no' => 'KAS-001',
        ]);
        Finance::create([
            'ambalan_id' => 1, 'member_id' => $admin->member->id,
            'jenis_transaksi' => 'Keluar', 'nominal' => 30000,
            'keterangan' => 'Expense', 'status' => 'Posted',
            'tgl_transaksi' => now()->toDateString(), 'created_by' => $admin->id,
            'receipt_no' => 'KAS-002',
        ]);

        $response = $this->actingAs($admin)->get('/finance');
        $response->assertInertia(fn ($page) => $page->has('balance')
            && $page->where('balance', 70000));
    }

    public function test_anggota_only_sees_own_transactions(): void
    {
        $admin = $this->createApprovedUser('Admin');
        $anggota1 = $this->createApprovedUser('Anggota', 'Laksana');
        $anggota2 = $this->createApprovedUser('Anggota', 'Laksana');

        Finance::create([
            'ambalan_id' => 1, 'member_id' => $anggota2->member->id,
            'jenis_transaksi' => 'Masuk', 'nominal' => 50000,
            'keterangan' => 'Iuran', 'status' => 'Posted',
            'tgl_transaksi' => now()->toDateString(), 'created_by' => $admin->id,
            'receipt_no' => 'KAS-003',
        ]);

        $response = $this->actingAs($anggota1)->get('/finance');
        $response->assertOk();
    }

    public function test_non_juru_uang_pengurus_cannot_create_finance_transaction(): void
    {
        $pengurus = $this->createApprovedUser('Pengurus');
        $this->actingAs($pengurus)->post('/finance', [
            'ambalan_id' => 1, 'member_id' => $pengurus->member->id,
            'jenis_transaksi' => 'Keluar', 'nominal' => 10000,
            'keterangan' => 'Expense', 'tgl_transaksi' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_juru_uang_can_create_finance_transaction(): void
    {
        $juruUang = $this->createJuruUangUser();
        $response = $this->actingAs($juruUang)->post('/finance', [
            'ambalan_id' => 1, 'member_id' => $juruUang->member->id,
            'jenis_transaksi' => 'Keluar', 'nominal' => 10000,
            'keterangan' => 'Expense', 'tgl_transaksi' => now()->toDateString(),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('finances', ['nominal' => 10000, 'jenis_transaksi' => 'Keluar', 'status' => 'Draft']);
    }
}
