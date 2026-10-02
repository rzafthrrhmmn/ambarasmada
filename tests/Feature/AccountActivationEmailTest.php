<?php

namespace Tests\Feature;

use App\Models\Angkatan;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountActivationEmailTest extends TestCase
{
    use RefreshDatabase;

    private function seedAngkatan(): void
    {
        Angkatan::create([
            'angkatan' => '2026',
            'nomor' => '030',
            'nama' => 'Angkatan 30',
            'is_active' => true,
            'is_current' => true,
        ]);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'username' => '31082008.30.001',
            'name' => 'Anggota Uji',
            'email' => 'anggota@example.test',
            'password' => Hash::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'pending',
        ], $overrides));
    }

    public function test_registration_sends_activation_email(): void
    {
        Notification::fake();
        $this->seedAngkatan();

        $response = $this->post('/register', [
            'nama_lengkap' => 'Anggota Baru',
            'email' => 'baru@example.test',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
        ]);

        $response->assertRedirect(route('login'));

        $user = User::where('email', 'baru@example.test')->firstOrFail();

        Notification::assertSentTo(
            $user,
            AccountActivationNotification::class,
            function (AccountActivationNotification $notification, array $channels, User $notifiable) {
                return $notifiable->email === 'baru@example.test'
                    && $notifiable->email_verified_at === null;
            }
        );

        $this->assertSame('baru@example.test', $user->username);

        $flash = (string) session('success');
        $this->assertStringContainsString('baru@example.test', $flash);
        $this->assertStringContainsString('NTA', $flash);
        $this->assertStringContainsString('Email aktivasi telah dikirim', $flash);
    }

    public function test_registration_assigns_username_from_email(): void
    {
        Notification::fake();
        $this->seedAngkatan();

        $this->post('/register', [
            'nama_lengkap' => 'Anggota Baru',
            'email' => 'budi.santoso@smandapari.example.test',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
        ]);

        $user = User::where('email', 'budi.santoso@smandapari.example.test')->firstOrFail();

        // Email panjang pun harus aman: kolom username pernah varchar(30).
        $this->assertSame('budi.santoso@smandapari.example.test', $user->username);
    }

    public function test_activation_email_carries_a_signed_verification_link(): void
    {
        $user = $this->makeUser();

        $notification = new AccountActivationNotification;
        $url = (new \ReflectionMethod($notification, 'verificationUrl'))
            ->invoke($notification, $user);

        $this->assertStringContainsString(
            route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]),
            $url,
        );

        $this->assertTrue(URL::hasValidSignature(
            Request::create($url)
        ));
    }

    public function test_guest_can_request_activation_link_by_email(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $response = $this->post('/email/resend-activation', ['email' => $user->email]);

        $response->assertRedirect();
        Notification::assertSentToTimes($user, AccountActivationNotification::class, 1);
    }

    public function test_guest_resend_does_not_leak_whether_email_is_registered(): void
    {
        Notification::fake();

        $known = $this->post('/email/resend-activation', ['email' => 'baru@example.test']);
        $unknown = $this->post('/email/resend-activation', ['email' => 'tidak-terdaftar@example.test']);

        $this->assertSame($known->baseResponse->getSession()->get('success'), $unknown->baseResponse->getSession()->get('success'));
    }

    public function test_guest_resend_requires_login_to_verify(): void
    {
        $user = $this->makeUser();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect(route('login'));

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_logged_in_user_can_resend_activation_email(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $response = $this->actingAs($user)
            ->post('/email/verification-notification');

        $response->assertRedirect();
        Notification::assertSentToTimes($user, AccountActivationNotification::class, 1);
    }

    public function test_verifying_marks_email_as_verified_for_the_signed_user(): void
    {
        Event::fake([Verified::class]);
        $user = $this->makeUser();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_user_cannot_verify_someone_elses_email_while_logged_in(): void
    {
        $victim = $this->makeUser();
        $attacker = $this->makeUser([
            'username' => '31082008.30.002',
            'email' => 'penyerang@example.test',
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $victim->id,
            'hash' => sha1($victim->email),
        ]);

        $this->actingAs($attacker)->get($url)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error');

        $this->assertFalse($victim->fresh()->hasVerifiedEmail());
    }

    public function test_expired_signature_is_rejected_with_actionable_message(): void
    {
        $user = $this->makeUser();

        $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_hash_is_rejected(): void
    {
        $user = $this->makeUser();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1('email-lain@example.test'),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_login_redirects_unverified_user_to_activation_page(): void
    {
        Notification::fake();
        $user = $this->makeUser(['status' => 'approved']);

        $response = $this->post('/login', [
            'identity' => $user->username,
            'password' => 'rahasia12345',
        ]);

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_pending_unverified_user_is_sent_to_approval_page_with_resend_option(): void
    {
        Notification::fake();
        $user = $this->makeUser(['status' => 'pending']);

        $this->post('/login', [
            'identity' => $user->username,
            'password' => 'rahasia12345',
        ])->assertRedirect(route('pending-approval'));

        // Halaman persetujuan tetap menjadi titik masuk untuk mengirim ulang.
        $this->post('/email/verification-notification');
        Notification::assertSentToTimes($user, AccountActivationNotification::class, 1);
    }

    public function test_login_forwards_pending_intended_verification_url(): void
    {
        $user = $this->makeUser();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        // Membuka tautan aktivasi tanpa login → middleware auth menyimpan URL.
        $this->get($url)->assertRedirect(route('login'));

        $this->post('/login', [
            'identity' => $user->username,
            'password' => 'rahasia12345',
        ])->assertRedirect($url);

        // Ikuti redirect tersebut, persis seperti yang dilakukan browser.
        $this->get($url)->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_skipped_for_already_verified_accounts(): void
    {
        Notification::fake();
        $user = $this->makeUser(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/email/verification-notification');

        Notification::assertNothingSent();
    }

    public function test_send_is_skipped_when_mailer_credentials_missing(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'ambarasmada@gmail.com',
            'mail.mailers.smtp.password' => null,
        ]);

        $user = $this->makeUser(['email' => 'tanpa-kredensial@example.test']);

        $this->assertFalse(
            $user->sendActivationEmail(),
            'Pengiriman harus ditolak bila password mailer kosong.'
        );
    }

    public function test_send_is_skipped_when_resend_api_key_missing(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => null,
        ]);

        $user = $this->makeUser(['email' => 'tanpa-apikey@example.test']);

        $this->assertFalse(
            $user->sendActivationEmail(),
            'Pengiriman harus ditolak bila RESEND_API_KEY kosong.'
        );
    }

    public function test_real_send_produces_a_message_with_recipient_and_both_parts(): void
    {
        // Sengaja TIDAK memakai Notification::fake(): yang diuji adalah jalur
        // pengiriman nyata. MailChannel::send() mengirim Mailable secara
        // langsung, sehingga penerima harus benar-benar terisi.
        config(['mail.default' => 'array']);

        $user = $this->makeUser(['email' => 'real-send@example.test']);

        $this->assertTrue($user->sendActivationEmail(), 'Pengiriman aktivasi gagal.');

        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        $sent = $transport->messages();
        $this->assertCount(1, $sent);

        $message = $sent[0]->getOriginalMessage();

        $recipients = $message->getTo();
        $this->assertNotEmpty($recipients, 'Email harus punya header To.');
        $this->assertSame('real-send@example.test', $recipients[0]->getAddress());

        $body = $message->getBody();
        $types = array_map(
            fn ($part) => $part->getMediaType().'/'.$part->getMediaSubtype(),
            $body->getParts()
        );

        $this->assertContains('text/plain', $types, 'Bagian text/plain wajib ada.');
        $this->assertContains('text/html', $types, 'Bagian text/html wajib ada.');
    }
}
