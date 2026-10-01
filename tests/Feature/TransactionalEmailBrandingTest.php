<?php

namespace Tests\Feature;

use App\Models\Ambalan;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\PasswordResetNotification;
use App\Support\MailBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionalEmailBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'username' => '31082008.018.001',
            'name' => 'Anggota Uji',
            'email' => 'anggota@example.test',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
        ]);
    }

    private function render(object $notification, User $user): string
    {
        return $notification->toMail($user)->render();
    }

    public function test_logo_url_is_absolute_and_publicly_reachable(): void
    {
        $url = MailBranding::logoUrl();

        $this->assertStringStartsWith('http', $url);
        $this->assertStringContainsString(MailBranding::fallbackLogoPath(), $url);
    }

    public function test_logo_url_prefers_absolute_uploaded_logo(): void
    {
        Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => 'AMB',
            'status' => 'aktif',
            'logo_path' => 'https://cdn.example.test/logo.png',
        ]);

        $this->assertSame('https://cdn.example.test/logo.png', MailBranding::logoUrl());
    }

    public function test_logo_url_ignores_non_url_logo_path(): void
    {
        Ambalan::create([
            'nama' => 'Ambalan Uji',
            'kode' => 'AMB',
            'status' => 'aktif',
            'logo_path' => 'logos/bukan-url.png',
        ]);

        $this->assertStringContainsString(
            MailBranding::fallbackLogoPath(),
            MailBranding::logoUrl()
        );
    }

    public function test_activation_email_contains_logo_and_branding(): void
    {
        $html = $this->render(new AccountActivationNotification, $this->makeUser());

        $this->assertStringContainsString(MailBranding::logoUrl(), $html);
        $this->assertStringContainsString('Aktifkan Akun Sekarang', $html);
        $this->assertStringContainsString('31082008.018.001', $html);
        $this->assertStringContainsString('Anggota Uji', $html);
        $this->assertStringContainsString('Aktivasi Akun', $html);
        $this->assertStringNotContainsString('Reset Password', $html);
    }

    public function test_password_reset_email_contains_logo_and_branding(): void
    {
        $html = $this->render(new PasswordResetNotification('token-uji'), $this->makeUser());

        $this->assertStringContainsString(MailBranding::logoUrl(), $html);
        $this->assertStringContainsString('Atur Password Baru', $html);
        $this->assertStringContainsString('31082008.018.001', $html);
        $this->assertStringContainsString('Password Baru', $html);
        $this->assertStringNotContainsString('Aktifkan Akun Sekarang', $html);
    }

    public function test_both_email_types_use_distinct_templates(): void
    {
        $user = $this->makeUser();

        $activation = $this->render(new AccountActivationNotification, $user);
        $reset = $this->render(new PasswordResetNotification('token-uji'), $user);

        $this->assertNotSame($activation, $reset);
        $this->assertStringContainsString('Aktifkan Akun', $activation);
        $this->assertStringContainsString('Atur Password Baru', $reset);
    }

    public function test_email_html_uses_ambalan_brand_colours(): void
    {
        $html = $this->render(new PasswordResetNotification('token-uji'), $this->makeUser());

        foreach (['#263D26', '#335233', '#6F9435', '#A7B92A', '#EDD330', '#f0ead8', '#8fa06a'] as $color) {
            $this->assertStringContainsString($color, $html, "Warna {$color} tidak ditemukan pada template email.");
        }
    }

    public function test_email_view_files_exist(): void
    {
        $this->assertFileExists(resource_path('views/emails/layout.blade.php'));
        $this->assertFileExists(resource_path('views/emails/account-activation.blade.php'));
        $this->assertFileExists(resource_path('views/emails/password-reset.blade.php'));
    }
}
