<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kotak notifikasi flash.
 *
 * Halaman selalu mengirim 'success' dan 'error' walau keduanya kosong. Sisi
 * klien yang hanya memeriksa jumlah key langsung memunculkan kotak kosong
 * setiap kali halaman dimuat, termasuk setelah refresh. Karena itu flash yang
 * dibagikan ke halaman harus benar-benar berisi pesan.
 */
class FlashMessageTest extends TestCase
{
    use RefreshDatabase;

    private const COMPONENT = 'resources/js/Components/FlashMessage.vue';

    public function test_halaman_tanpa_pesan_tidak_mengirim_flash_kosong(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('flash', []));
    }

    public function test_pesan_berhasil_dikirim_apa_adanya(): void
    {
        session()->flash('success', 'Anggota berhasil ditambahkan.');

        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Anggota berhasil ditambahkan.')
                ->missing('flash.error')
            );
    }

    public function test_pesan_gagal_dikirim_apa_adanya(): void
    {
        session()->flash('error', 'Anggota gagal disimpan.');

        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.error', 'Anggota gagal disimpan.')
                ->missing('flash.success')
            );
    }

    /**
     * Flash kosong dari pengirim yang salah-handle tidak boleh membuat kotak
     * kosong muncul, jadi pesannya dibuang, bukan diteruskan apa adanya.
     */
    public function test_pesan_kosong_dibuang(): void
    {
        session()->flash('success', '');
        session()->flash('error', '   ');

        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('flash', []));
    }

    public function test_flash_lainnya_juga_diteruskan(): void
    {
        session()->flash('warning', 'Sesi Anda telah diperbarui.');

        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.warning', 'Sesi Anda telah diperbarui.')
            );
    }

    /**
     * Sisi klien tidak boleh menampilkan apa pun kalau flash yang diterima tidak
     * punya isi. Pemeriksaan ini menjaga perbaikan di middleware tetap perlu
     * dipagarkan komponennya.
     */
    public function test_komponen_tidak_menampilkan_pesan_kosong(): void
    {
        $source = file_get_contents(base_path(static::COMPONENT));

        $this->assertStringNotContainsString('Object.keys(flash).length > 0', (string) $source);
        $this->assertStringContainsString('if (!picked)', (string) $source);
    }

    private function makeUser(): User
    {
        return User::create([
            'username' => 'flash.pesan@test.local',
            'name' => 'Penguji Flash',
            'email' => 'flash.pesan@test.local',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }
}
