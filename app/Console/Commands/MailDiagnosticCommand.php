<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailDiagnosticCommand extends Command
{
    protected $signature = 'mail:diagnose {--email= : Alamat email tujuan uji coba (opsional)}';

    protected $description = 'Periksa konfigurasi mailer dan uji kirim email aktivasi.';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');

        $this->line("MAILER          : {$mailer}");
        $this->line("FROM            : {$from}");
        $this->line('APP_URL         : '.(string) config('app.url'));

        if ($mailer === 'smtp') {
            $this->line('HOST/PORT       : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
            $this->line('SCHEME          : '.(config('mail.mailers.smtp.scheme') ?? 'null (auto STARTTLS)'));
            $this->line('USERNAME        : '.(filled(config('mail.mailers.smtp.username')) ? 'terisi' : 'KOSONG'));
            $this->line('PASSWORD        : '.(filled(config('mail.mailers.smtp.password')) ? 'terisi' : 'KOSONG'));
        }

        if ($mailer === 'resend') {
            $this->line('RESEND_API_KEY  : '.(filled(config('services.resend.key')) ? 'terisi' : 'KOSONG'));
        }

        $this->newLine();

        $target = $this->option('email');

        if (! $target) {
            $this->components->info('Kredensial mailer terbaca. Tambahkan --email untuk uji kirim.');
            $this->line('  php artisan mail:diagnose --email=alamat@contoh.test');
            $this->components->warn('Hindari alamat orang lain saat menguji.');

            return self::SUCCESS;
        }

        $this->components->info("Mengirim email aktivasi uji ke {$target} ...");

        $user = new User([
            'username' => $target,
            'name' => 'Uji Aktivasi',
            'email' => $target,
        ]);
        $user->id = 0;
        $user->exists = true;

        try {
            $mailable = (new AccountActivationNotification)->toMail($user);

            $recipients = $mailable->to ?? [];
            $this->line('Header To       : '.(empty($recipients) ? 'KOSONG (bug!)' : (string) ($recipients[0]['address'] ?? 'KOSONG (bug!)')));

            $mailable->send(Mail::mailer());

            $this->components->info('BERHASIL terkirim. Periksa inbox (dan folder spam).');
        } catch (Throwable $e) {
            $this->components->error('GAGAL: '.$e->getMessage());
            Log::error('[mail:diagnose] '.$e->getMessage(), ['exception' => $e]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
