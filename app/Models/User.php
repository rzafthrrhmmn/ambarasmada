<?php

namespace App\Models;

use App\Notifications\AccountActivationNotification;
use App\Notifications\PasswordResetNotification;
use App\Support\MailBranding;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Fillable(['username', 'name', 'email', 'password', 'role', 'is_active', 'status', 'foto', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    protected $fillable = ['username', 'name', 'email', 'password', 'role', 'is_active', 'status', 'foto', 'email_verified_at'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function skuSubmissions(): HasMany
    {
        return $this->hasMany(SkuSubmission::class, 'verified_by');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'verified_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Kirim email aktivasi akun secara sinkron.
     *
     * Sengaja tidak diantrikan: deployment Vercel tidak menjalankan queue
     * worker, sehingga job berantrean akan menggantung di tabel `jobs`.
     *
     * @return bool true bila email berhasil diserahkan ke mailer
     */
    public function sendActivationEmail(): bool
    {
        if ($this->email === null || $this->email === '') {
            Log::warning('[activation-email] Gagal kirim: pengguna tanpa email.', ['user_id' => $this->getKey()]);

            return false;
        }

        if ($this->hasVerifiedEmail()) {
            return false;
        }

        if (! $this->mailerIsConfigured()) {
            return false;
        }

        try {
            $this->notify(new AccountActivationNotification);

            Log::info('[activation-email] Email aktivasi berhasil dikirim.', [
                'user_id' => $this->getKey(),
                'username' => $this->username,
                'email' => $this->email,
                'mailer' => config('mail.default'),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('[activation-email] Email aktivasi gagal dikirim: '.$e->getMessage(), [
                'user_id' => $this->getKey(),
                'email' => $this->email,
                'mailer' => config('mail.default'),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Pastikan mailer yang aktif punya kredensial yang diperlukan, supaya
     * kegagalan bisa di diagnosa dari log alih-alih melempar exception.
     */
    private function mailerIsConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        $missing = match ($mailer) {
            'smtp' => array_values(array_filter(
                ['MAIL_HOST' => config('mail.mailers.smtp.host'), 'MAIL_USERNAME' => config('mail.mailers.smtp.username'), 'MAIL_PASSWORD' => config('mail.mailers.smtp.password')],
                fn ($value) => blank($value)
            )),
            'resend' => blank(config('services.resend.key')) ? ['RESEND_API_KEY' => true] : [],
            default => [],
        };

        if ($missing !== []) {
            Log::error('[activation-email] Mailer belum dikonfigurasi, email tidak dapat dikirim.', [
                'user_id' => $this->getKey(),
                'mailer' => $mailer,
                'missing' => array_keys($missing),
            ]);

            return false;
        }

        // Pencocokan domain link hanya relevan bagi provider yang memiliki
        // konsep domain pengirim (Resend). Gmail memakai gmail.com sehingga
        // tidak akan pernah cocok dengan APP_URL.
        if ($mailer === 'resend') {
            $this->warnOnLinkDomainMismatch();
        }

        return true;
    }

    /**
     * Mengganti implementasi trait MustVerifyEmail agar memakai notifikasi
     * aktivasi kustom AMBARA.
     *
     * Tanpa return type agar tetap kompatibel dengan trait.
     */
    public function sendEmailVerificationNotification()
    {
        $this->sendActivationEmail();
    }

    /**
     * Peringatan bila link verifikasi/logo di dalam email tidak berasal dari
     * domain yang sama dengan MAIL_FROM_ADDRESS. Kondisi ini menurunkan
     * deliverability dan ditandai Resend sebagai "needs attention".
     */
    private function warnOnLinkDomainMismatch(): void
    {
        $status = MailBranding::linkDomainStatus();

        if ($status['matches']) {
            return;
        }

        Log::warning('[activation-email] Host link di email tidak sama dengan domain pengirim.', [
            'app_url_host' => $status['app_host'],
            'mail_from_host' => $status['sending_host'],
            'hint' => 'Samakan APP_URL dengan domain MAIL_FROM_ADDRESS agar link dan logo email memakai domain pengirim.',
        ]);
    }

    /**
     * Mengganti notifikasi reset password bawaan Laravel dengan versi AMBARA.
     *
     * Tanpa return type agar tetap kompatibel dengan trait CanResetPassword.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'status' => 'string',
            'password' => 'hashed',
        ];
    }
}
