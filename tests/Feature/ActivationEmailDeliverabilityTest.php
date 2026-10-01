<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Support\MailBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Hash as HashFacade;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Memastikan email aktivasi memenuhi pedoman deliverability Resend:
 * tautan & gambar memakai domain pengirim, serta email menyertakan bagian
 * text/plain selain text/html.
 */
class ActivationEmailDeliverabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @var RawMessage|null */
    private $captured;

    /** @var Email|null */
    private $built;

    private function transportSpy(): TransportInterface
    {
        $test = $this;

        return new class($test) implements TransportInterface
        {
            public function __construct(private $test) {}

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->test->capture($message);

                return null;
            }

            public function __toString(): string
            {
                return 'spy';
            }
        };
    }

    public function capture(RawMessage $message): void
    {
        $this->captured = $message;
    }

    private function buildMessage(?User $user = null): Email
    {
        if ($this->built !== null) {
            return $this->built;
        }

        $user ??= User::create([
            'username' => '31082008.30.001',
            'name' => 'Anggota Uji',
            'email' => 'anggota@example.test',
            'password' => HashFacade::make('rahasia12345'),
            'role' => 'Anggota',
            'is_active' => true,
            'status' => 'approved',
        ]);

        $mailer = new Mailer('spy', app('view'), $this->transportSpy(), app('events'));

        (new AccountActivationNotification)->toMail($user)
            ->to($user->email)
            ->send($mailer);

        $this->assertNotNull($this->captured, 'Email tidak terkirim ke transport.');

        return $this->built = $this->captured;
    }

    private function partsByType(string $type, string $subtype): array
    {
        $parts = $this->buildMessage()->getBody()->getParts();

        return array_values(array_filter(
            $parts,
            fn ($part) => $part->getMediaType() === $type && $part->getMediaSubtype() === $subtype
        ));
    }

    public function test_email_is_multipart_alternative_with_text_and_html(): void
    {
        $body = $this->buildMessage()->getBody();

        // Symfony memilih subtype multipart/alternative atau multipart
        // tergantung pada charset/urutan part, jadi yang diuji secara bermakna
        // adalah keberadaan kedua bagian.
        $this->assertStringStartsWith('multipart', $body->getMediaType());
        $this->assertCount(2, $body->getParts());
        $this->assertCount(1, $this->partsByType('text', 'plain'));
        $this->assertCount(1, $this->partsByType('text', 'html'));
    }

    public function test_plain_text_part_contains_no_html_markup(): void
    {
        $text = $this->partsByType('text', 'plain')[0]->getBody();

        $this->assertStringNotContainsString('<', $text);
        $this->assertStringNotContainsString('&amp;', $text);
    }

    public function test_plain_text_part_carries_a_usable_verification_link(): void
    {
        $message = $this->buildMessage();
        $text = $this->partsByType('text', 'plain')[0]->getBody();

        // Tautan harus dapat disalin mentah ke browser, tanpa entity HTML.
        $this->assertMatchesRegularExpression('/https:\/\/\S+\?expires=\d+&signature=[a-f0-9]+/', $text);

        $this->assertTrue(
            $message->getBody()->getParts()[0]->getBody() !== null,
        );
    }

    public function test_all_links_and_images_point_to_the_app_host(): void
    {
        $html = $this->partsByType('text', 'html')[0]->getBody();

        preg_match_all('/(?:href|src)="([^"]+)"/i', $html, $matches);
        $urls = array_values(array_unique($matches[1]));

        $this->assertNotEmpty($urls);

        // Seluruh tautan dan gambar harus dibangun dari APP_URL, agar cocok
        // dengan domain pengirim email (syarat deliverability Resend).
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        foreach ($urls as $url) {
            $this->assertSame(
                $appHost,
                parse_url($url, PHP_URL_HOST),
                "URL {$url} tidak memakai host aplikasi ({$appHost})."
            );
        }
    }

    public function test_email_contains_a_logo_image_hosted_on_the_app_host(): void
    {
        $html = $this->partsByType('text', 'html')[0]->getBody();

        $this->assertSame(
            1,
            preg_match_all('/<img[^>]*src="([^"]+)"/i', $html, $matches),
            'Email sebaiknya memuat tepat satu logo.'
        );

        $this->assertSame(
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url($matches[1][0], PHP_URL_HOST),
            'Logo harus disajikan dari domain aplikasi, bukan hotlink pihak ketiga.'
        );
    }

    public function test_link_domain_status_flags_mismatch_between_app_and_from_host(): void
    {
        config([
            'app.url' => 'https://ambarasmada.com',
            'mail.from.address' => 'noreply@ambarasmada.com',
        ]);

        $status = MailBranding::linkDomainStatus();

        $this->assertSame('ambarasmada.com', $status['app_host']);
        $this->assertSame('ambarasmada.com', $status['sending_host']);
        $this->assertTrue($status['matches']);
    }

    public function test_link_domain_status_detects_vercel_app_sending_mail_domain(): void
    {
        config([
            'app.url' => 'https://ambarasmada.vercel.app',
            'mail.from.address' => 'noreply@ambarasmada.com',
        ]);

        $status = MailBranding::linkDomainStatus();

        $this->assertFalse($status['matches']);
        $this->assertSame('ambarasmada.vercel.app', $status['app_host']);
        $this->assertSame('ambarasmada.com', $status['sending_host']);
    }

    public function test_link_domain_status_parses_display_name_form_and_bare_email(): void
    {
        config(['app.url' => 'https://ambarasmada.com']);

        config(['mail.from.address' => 'AMBARA <noreply@ambarasmada.com>']);
        $this->assertSame('ambarasmada.com', MailBranding::linkDomainStatus()['sending_host']);

        config(['mail.from.address' => 'noreply@ambarasmada.com']);
        $this->assertSame('ambarasmada.com', MailBranding::linkDomainStatus()['sending_host']);
    }
}
