<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\Article;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman detail artikel.
 *
 * `ArticleController::show()` sudah ada sejak awal, tetapi rutenya tidak pernah
 * didaftarkan, sehingga setiap tautan ke detail artikel berakhir 404 karena
 * tidak ada halaman Inertia yang cocok. Test di sini menjaga agar rute, halaman,
 * dan aturan visibilitas draf tetap terus berjalan.
 */
class ArticleShowPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $anggota;

    protected function setUp(): void
    {
        parent::setUp();
        Ambalan::create(['kode' => 'TEST', 'nama' => 'Test Ambalan']);
        $this->admin = $this->createUser('Admin');
        $this->anggota = $this->createUser('Anggota');
    }

    public function test_published_article_detail_renders_inertia_page(): void
    {
        $article = $this->makeArticle(['is_published' => true]);

        $this->actingAs($this->anggota)
            ->get("/articles/{$article->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Articles/Show')
                ->where('article.judul', $article->judul)
                ->where('article.konten', $article->konten)
                ->has('canManage')
                ->has('canDelete')
            );
    }

    public function test_article_detail_requires_authentication(): void
    {
        $article = $this->makeArticle(['is_published' => true]);

        $this->get("/articles/{$article->id}")->assertRedirect('/login');
    }

    /*
     * Daftar hanya menampilkan artikel yang sudah terbit. Artikel draf tidak
     * boleh bocor lewat URL, jadi jenis jawabannya 404, bukan 403: kalau 403,
     * penyerang tahu pasti artikel itu ada.
     */
    public function test_draft_article_is_hidden_from_other_members(): void
    {
        $article = $this->makeArticle(['is_published' => false]);

        $this->actingAs($this->anggota)
            ->get("/articles/{$article->id}")
            ->assertNotFound();
    }

    public function test_author_can_open_their_own_draft(): void
    {
        $article = $this->makeArticle(['is_published' => false, 'author_id' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->get("/articles/{$article->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Articles/Show')->where('canManage', true));
    }

    public function test_admin_can_open_any_draft_and_may_delete_it(): void
    {
        $article = $this->makeArticle(['is_published' => false, 'author_id' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->get("/articles/{$article->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Articles/Show')
                ->where('canDelete', true)
            );
    }

    /*
     * Penentuannya harus berasal dari server. Halaman cukup membaca
     * `can_edit` dan `can_delete` per kartu, jadi kalau aturan ini hilang
     * tombol Edit/Hapus muncul untuk semua orang lalu ditolak 403.
     */
    public function test_index_marks_per_article_edit_and_delete_permissions(): void
    {
        $mine = $this->makeArticle(['is_published' => true, 'author_id' => $this->admin->id]);
        $other = $this->makeArticle(['is_published' => true, 'author_id' => $this->anggota->id]);

        // Urutan daftar memakai `created_at`, jadi baris dicari lewat id-nya
        // dan bukan berdasarkan posisi.
        $flagsFor = function (string $role) use ($mine, $other): array {
            $response = $this->actingAs($this->{$role})
                ->get('/articles')
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component('Articles/Index'));

            $rows = collect($response->viewData('page')['props']['articles']['data'])->keyBy('id');

            return [
                'mine' => (bool) $rows[$mine->id]['can_edit'],
                'mine_delete' => (bool) $rows[$mine->id]['can_delete'],
                'other' => (bool) $rows[$other->id]['can_edit'],
                'other_delete' => (bool) $rows[$other->id]['can_delete'],
            ];
        };

        // Admin dan Pembina boleh menyunting dan menghapus artikel apa pun.
        $this->assertSame(
            ['mine' => true, 'mine_delete' => true, 'other' => true, 'other_delete' => true],
            $flagsFor('admin'),
        );

        // Anggota hanya boleh menyunting artikelnya sendiri, dan tidak pernah
        // boleh menghapus.
        $this->assertSame(
            ['mine' => false, 'mine_delete' => false, 'other' => true, 'other_delete' => false],
            $flagsFor('anggota'),
        );
    }

    /*
     * `image_url` dibaca `Articles/Index.vue` dan `Articles/Show.vue`. Tanpa
     * accessor itu kedua halaman hanya melihat `undefined`, jadi gambar
     * sampul yang sudah diunggah tidak pernah muncul tanpa error terlihat.
     */
    public function test_image_url_accessor_is_available_for_the_frontend(): void
    {
        $this->assertNull($this->makeArticle()->image_url);

        $withImage = $this->makeArticle(['image' => 'articles/gallery/contoh.jpg']);
        $this->assertIsString($withImage->image_url);
        $this->assertStringContainsString('articles/gallery/contoh.jpg', $withImage->image_url);
    }

    private function makeArticle(array $attributes = []): Article
    {
        return Article::create([
            'judul' => 'Laporan Kegiatan Latihan',
            'konten' => "Paragraf pertama.\n\nParagraf kedua.",
            'kategori' => 'Laporan Kegiatan',
            'is_published' => true,
            'ambalan_id' => 1,
            'author_id' => $this->admin->id,
            ...$attributes,
        ]);
    }

    private function createUser(string $role): User
    {
        $user = User::create([
            'username' => $role.'_'.uniqid(),
            'name' => ucfirst($role),
            'email' => strtolower($role).'_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);

        Member::create([
            'user_id' => $user->id,
            'ambalan_id' => 1,
            'nta' => 'nta_'.uniqid(),
            'nama_lengkap' => $user->name,
            'kelas' => 'X',
            'tingkatan' => 'Bantara',
            'status' => 'aktif',
        ]);

        return $user;
    }
}