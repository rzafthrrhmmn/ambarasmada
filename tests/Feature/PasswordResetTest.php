<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'anggota@test.com'): User
    {
        return User::create([
            'username' => '31082008.018.001',
            'name' => 'Anggota Test',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }

    public function test_guest_can_view_forgot_password_page(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_guest_can_view_reset_password_page(): void
    {
        $this->get('/reset-password/sample-token?email=anggota@test.com')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'sample-token')
                ->where('email', 'anggota@test.com')
                ->where('expiryMinutes', 60)
            );
    }

    public function test_reset_link_email_is_sent_for_registered_email(): void
    {
        Notification::fake();

        $user = $this->makeUser();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'anggota@test.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success');

        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_forgot_password_fails_for_unregistered_email(): void
    {
        Notification::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'tidak@ada.test.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_forgot_password_requires_valid_email_format(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'bukan-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_forgot_password_requires_email_field(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', [])
            ->assertSessionHasErrors('email');
    }

    public function test_reset_link_email_uses_custom_ambara_notification(): void
    {
        Notification::fake();

        $user = $this->makeUser();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'anggota@test.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $user,
            PasswordResetNotification::class,
            function ($notification, $channels, $user) {
                $mail = $notification->toMail($user);

                $expectedUrl = route('password.reset', [
                    'token' => $notification->token,
                    'email' => 'anggota@test.com',
                ], false);

                return $notification->token !== null
                    && str_contains($mail->subject, 'Reset Password')
                    && str_contains($mail->subject, '31082008.018.001')
                    && str_contains($mail->render(), htmlspecialchars($expectedUrl, ENT_QUOTES));
            }
        );
    }

    public function test_custom_reset_email_mentions_account_identity_and_expiry(): void
    {
        Notification::fake();

        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => 'anggota@test.com']);

        Notification::assertSentTo($user, PasswordResetNotification::class, function ($notification, $channels, $user) {
            $html = $notification->toMail($user)->render();

            return str_contains($html, 'Halo Anggota Test')
                && str_contains($html, 'Anggota Test')
                && str_contains($html, '31082008.018.001')
                && str_contains($html, 'anggota@test.com')
                && str_contains($html, 'berlaku selama')
                && str_contains($html, '60 menit')
                && str_contains($html, 'Password Anda tetap aman')
                && PasswordResetNotification::expiryMinutes() === 60;
        });
    }

    public function test_custom_reset_email_is_distinct_from_activation_email(): void
    {
        $user = $this->makeUser();

        $reset = new PasswordResetNotification('token-uji');
        $activation = new AccountActivationNotification;

        $this->assertNotSame(
            $reset->toMail($user)->subject,
            $activation->toMail($user)->subject
        );
        $this->assertNotSame(get_class($reset), get_class($activation));
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => 'anggota@test.com']);

        $token = Password::createToken($user);

        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => 'anggota@test.com',
                'password' => 'sandi-baru-123',
                'password_confirmation' => 'sandi-baru-123',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('sandi-baru-123', $user->fresh()->password));
    }

    public function test_password_reset_fails_with_wrong_confirmation(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => 'anggota@test.com',
                'password' => 'sandi-baru-123',
                'password_confirmation' => 'beda-sekali',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_password_reset_rejects_short_password(): void
    {
        Notification::fake();

        $user = $this->makeUser();
        $token = Password::createToken($user);

        $this->from('/reset-password/'.$token)
            ->post('/reset-password', [
                'token' => $token,
                'email' => 'anggota@test.com',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = $this->makeUser();

        $this->from('/reset-password/token-salah')
            ->post('/reset-password', [
                'token' => 'token-salah',
                'email' => 'anggota@test.com',
                'password' => 'sandi-baru-123',
                'password_confirmation' => 'sandi-baru-123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_new_password_allows_login_with_identity_flow(): void
    {
        Notification::fake();

        $this->makeUser();

        $this->post('/forgot-password', ['email' => 'anggota@test.com']);

        $user = User::where('email', 'anggota@test.com')->first();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'anggota@test.com',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'identity' => '31082008.018.001',
            'password' => 'sandi-baru-123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_forgot_password_route_has_rate_limiting(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->get('/forgot-password');
        }

        $this->get('/forgot-password')->assertStatus(429);
    }
}
