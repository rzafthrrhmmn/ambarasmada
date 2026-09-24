<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Article;
use App\Models\Assessment;
use App\Models\Event;
use App\Models\FieldGuide;
use App\Models\Gallery;
use App\Models\Letter;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Reminder;
use App\Models\ReportController;
use App\Models\SkuPoint;
use App\Models\SkuSubmission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected ?User $admin = null;
    protected ?User $pembina = null;
    protected ?User $pengurus = null;
    protected ?User $anggota = null;

    protected function setUp(): void
    {
        parent::setUp();
        Ambalan::create(['kode' => 'TEST', 'nama' => 'Test Ambalan']);
    }

    protected function createUser(string $role): User
    {
        $user = User::create([
            'username' => $role . '_user_' . uniqid(),
            'name' => ucfirst($role),
            'email' => strtolower($role) . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
        ]);
        Member::create([
            'user_id' => $user->id,
            'ambalan_id' => 1,
            'nta' => $role . '_nta_' . uniqid(),
            'nama_lengkap' => ucfirst($role) . ' User',
            'kelas' => 'X',
            'tingkatan' => 'Bantara',
            'angkatan' => '018',
            'nomor_urut' => $user->id,
            'nta_username' => $role . '_user_' . uniqid(),
            'status_aktif' => 'Aktif',
        ]);
        return $user;
    }

    protected function getAdmin(): User { return $this->admin ??= $this->createUser('Admin'); }
    protected function getPembina(): User { return $this->pembina ??= $this->createUser('Pembina'); }
    protected function getPengurus(): User { return $this->pengurus ??= $this->createUser('Pengurus'); }
    protected function getAnggota(): User { return $this->anggota ??= $this->createUser('Anggota'); }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->getAdmin())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_anggota_can_access_dashboard(): void
    {
        $this->actingAs($this->getAnggota())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_team_destroy_forbidden_for_anggota(): void
    {
        $team = Team::create(['nama' => 'Test', 'kode' => 'T01', 'ambalan_id' => 1]);

        $this->actingAs($this->getAnggota())
            ->delete("/teams/{$team->id}")
            ->assertForbidden();
    }

    public function test_team_destroy_allows_pembina(): void
    {
        $team = Team::create(['nama' => 'Test', 'kode' => 'T01', 'ambalan_id' => 1]);

        $this->actingAs($this->getPembina())
            ->delete("/teams/{$team->id}")
            ->assertRedirect();
    }

    public function test_team_remove_member_works_with_request(): void
    {
        $team = Team::create(['nama' => 'Test', 'kode' => 'T01', 'ambalan_id' => 1]);
        $member = $this->getAnggota()->member;
        $teamMember = TeamMember::create([
            'team_id' => $team->id,
            'member_id' => $member->id,
            'peran' => 'Anggota',
        ]);

        $this->actingAs($this->getPembina())
            ->delete("/teams/{$team->id}/member/{$teamMember->id}")
            ->assertRedirect();
    }

    public function test_training_store_forbidden_for_anggota(): void
    {
        $this->actingAs($this->getAnggota())
            ->post('/trainings', ['judul' => 'Test', 'kategori' => 'Latihan'])
            ->assertForbidden();
    }

    public function test_training_destroy_forbidden_for_anggota(): void
    {
        $training = Training::create([
            'judul' => 'Test', 'kategori' => 'Latihan', 'ambalan_id' => 1,
            'created_by' => $this->getAdmin()->id,
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/trainings/{$training->id}")
            ->assertForbidden();
    }

    public function test_event_destroy_forbidden_for_anggota(): void
    {
        $event = Event::create([
            'nama' => 'Test', 'tanggal' => now(), 'ambalan_id' => 1,
            'created_by' => $this->getAdmin()->id, 'jenis' => 'Latihan',
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/events/{$event->id}")
            ->assertForbidden();
    }

    public function test_assessment_destroy_forbidden_for_anggota(): void
    {
        $assessment = Assessment::create([
            'member_id' => $this->getAnggota()->member->id,
            'ambalan_id' => 1, 'assessor_id' => $this->getAdmin()->id,
            'periode' => '2024', 'nilai_kehadiran' => 80,
            'nilai_disiplin' => 80, 'nilai_keterampilan' => 80,
            'nilai_kepemimpinan' => 80, 'nilai_keseluruhan' => 80, 'status' => 'Draft',
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/assessments/{$assessment->id}")
            ->assertForbidden();
    }

    public function test_sku_update_allows_admin(): void
    {
        $skuPoint = SkuPoint::create([
            'tingkatan' => 'Bantara', 'nomor_poin' => 1,
            'deskripsi_poin' => 'Test point', 'is_active' => true,
        ]);

        $this->actingAs($this->getAdmin())
            ->patch("/sku/points/{$skuPoint->id}", [
                'tingkatan' => 'Bantara', 'nomor_poin' => 1,
                'deskripsi_poin' => 'Updated', 'is_active' => true,
            ])
            ->assertRedirect();
    }

    public function test_sku_approve_forbidden_for_anggota(): void
    {
        $member = $this->getAnggota()->member;
        $point = SkuPoint::create([
            'tingkatan' => 'Bantara', 'nomor_poin' => 1,
            'deskripsi_poin' => 'Test', 'is_active' => true,
        ]);
        $submission = SkuSubmission::create([
            'member_id' => $member->id, 'sku_point_id' => $point->id,
            'bukti_kegiatan' => 'test.pdf', 'status' => 'Pending',
            'catatan' => 'Test submission',
        ]);

        $this->actingAs($this->getAnggota())
            ->post("/sku/{$submission->id}/approve")
            ->assertForbidden();
    }

    public function test_permission_sync_forbidden_for_anggota(): void
    {
        $targetUser = $this->getAnggota();

        $this->actingAs($this->getAnggota())
            ->post('/permissions/sync', [
                'user_id' => $targetUser->id,
                'permissions' => ['test_permission'],
            ])
            ->assertForbidden();
    }

    public function test_reminder_store_forbidden_for_anggota(): void
    {
        $this->actingAs($this->getAnggota())
            ->post('/reminders', [
                'user_id' => $this->getAnggota()->id,
                'judul' => 'Test',
                'jadwal' => now()->toDateTimeString(),
                'jenis' => 'Pengingat',
            ])
            ->assertForbidden();
    }

    public function test_field_guide_destroy_forbidden_for_anggota(): void
    {
        $guide = FieldGuide::create([
            'judul' => 'Test', 'konten' => 'Content', 'kategori' => 'Tanda Isyarat',
            'ambalan_id' => 1, 'created_by' => $this->getAdmin()->id,
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/field-guides/{$guide->id}")
            ->assertForbidden();
    }

    public function test_article_destroy_forbidden_for_anggota(): void
    {
        $article = Article::create([
            'judul' => 'Test', 'konten' => 'Content', 'kategori' => 'Artikel',
            'is_published' => true, 'ambalan_id' => 1, 'author_id' => $this->getAdmin()->id,
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/articles/{$article->id}")
            ->assertForbidden();
    }

    public function test_gallery_destroy_forbidden_for_anggota(): void
    {
        $gallery = Gallery::create([
            'judul' => 'Test', 'kategori' => 'Umum', 'image' => 'test.jpg',
            'ambalan_id' => 1, 'uploaded_by' => $this->getAdmin()->id,
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/galleries/{$gallery->id}")
            ->assertForbidden();
    }

    public function test_meeting_destroy_forbidden_for_anggota(): void
    {
        $meeting = Meeting::create([
            'judul' => 'Test Meet', 'agenda' => 'Test agenda', 'tanggal' => now(), 'ambalan_id' => 1,
            'created_by' => $this->getAdmin()->id, 'jenis' => 'Rapat Anggota',
        ]);

        $this->actingAs($this->getAnggota())
            ->delete("/meetings/{$meeting->id}")
            ->assertForbidden();
    }

    public function test_reminder_destroy_allowed_for_admin(): void
    {
        $owner = $this->getAnggota();
        $reminder = Reminder::create([
            'user_id' => $owner->id, 'judul' => 'Test',
            'jadwal' => now()->addDay()->toDateTimeString(),
            'jenis' => 'Pengingat', 'created_by' => $owner->id,
        ]);

        $this->actingAs($this->getAdmin())
            ->from('/reminders')
            ->delete("/reminders/{$reminder->id}")
            ->assertRedirect();
    }

    public function test_login_page_accessible_without_auth(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('login');
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
        $this->assertTrue(true, 'No duplicate route names found');
    }

    public function test_letter_download_returns_404_without_file(): void
    {
        $admin = $this->getAdmin();
        $letter = Letter::create([
            'perihal' => 'Surat Test', 'jenis_surat' => 'Keluar',
            'tujuan_pengirim' => 'Test', 'tgl_surat' => now()->toDateString(),
            'file_path' => null, 'ambalan_id' => 1,
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($this->getAdmin())
            ->get("/letters/{$letter->id}/download");

        $response->assertStatus(404);
    }

    public function test_pwa_device_registration_requires_auth(): void
    {
        $this->post('/pwa/devices')->assertRedirect('login');
    }

    public function test_sk_point_store_requires_pembina_or_admin(): void
    {
        $this->actingAs($this->getAnggota())
            ->post('/system/points', [
                'member_id' => $this->getAnggota()->member->id,
                'kategori' => 'Test', 'deskripsi' => 'Test point', 'poin' => 10,
            ])
            ->assertForbidden();
    }
}