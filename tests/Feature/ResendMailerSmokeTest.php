<?php

namespace Tests\Feature;

use Illuminate\Mail\Transport\ResendTransport;
use Tests\TestCase;

class ResendMailerSmokeTest extends TestCase
{
    public function test_resend_mailer_transport_is_available(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 're_test_dummy_key',
        ]);

        $this->assertInstanceOf(
            ResendTransport::class,
            app('mail.manager')->mailer()->getSymfonyTransport()
        );
    }
}
