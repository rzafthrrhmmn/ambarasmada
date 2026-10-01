<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email reset password AMBARA.
 *
 * Tipe email kedua, berpasangan dengan AccountActivationNotification yang
 * menangani email aktivasi akun. Keduanya memakai MailMessage sehingga
 * memanfaatkan template bawaan Laravel dan tetap tampil rapi di client email.
 *
 * Sengaja TIDAK mengimplementasikan ShouldQueue: aplikasi berjalan di Vercel
 * (serverless) tanpa queue worker, sehingga notifikasi berantrean akan
 * menumpuk di tabel `jobs` dan tidak pernah terkirim. Notifikasi dikirim
 * sinkron agar status pengiriman bisa langsung dilaporkan ke pengguna.
 *
 * URL reset diwarisi dari Illuminate\Auth\Notifications\ResetPassword, yaitu
 * signed URL + hash SHA1 email yang cocok dengan route `password.reset`.
 */
class PasswordResetNotification extends ResetPassword
{
    /**
     * Parent class hanya menerima notifiable sebagai argumen toMail(), bukan
     * sebagai property, sehingga kita menyimpannya sendiri di sini.
     *
     * @var mixed
     */
    protected $resetNotifiable = null;

    /**
     * Masa berlaku tautan reset dalam menit.
     */
    public static function expiryMinutes(): int
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);

        return $minutes > 0 ? $minutes : 60;
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable)
    {
        $this->resetNotifiable = $notifiable;

        return parent::toMail($notifiable);
    }

    /**
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        $notifiable = $this->resetNotifiable;

        return (new MailMessage)
            ->subject('Reset Password '.$this->appName().' - '.$this->attribute($notifiable, 'username', '-'))
            ->view('emails.password-reset', [
                'url' => $url,
                'appName' => $this->appName(),
                'ambalanName' => MailBranding::ambalanName(),
                'logoUrl' => MailBranding::logoUrl(),
                'displayName' => $this->displayName($notifiable),
                'username' => $this->attribute($notifiable, 'username', '-'),
                'email' => $this->emailOf($notifiable),
                'expiryMinutes' => self::expiryMinutes(),
            ]);
    }

    private function displayName($notifiable): string
    {
        return $this->attribute($notifiable, 'name', $this->emailOf($notifiable));
    }

    private function emailOf($notifiable): string
    {
        if (is_object($notifiable) && method_exists($notifiable, 'getEmailForPasswordReset')) {
            return (string) $notifiable->getEmailForPasswordReset();
        }

        return '-';
    }

    private function appName(): string
    {
        return (string) config('app.name', 'AMBARA-SISTEM DIGITAL');
    }

    private function attribute($notifiable, string $key, string $default = ''): string
    {
        if (! is_object($notifiable) || ! method_exists($notifiable, 'getAttribute')) {
            return $default;
        }

        $value = $notifiable->getAttribute($key);

        return $value === null || $value === '' ? $default : (string) $value;
    }
}
