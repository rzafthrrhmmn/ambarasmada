<?php

namespace App\Notifications;

use App\Support\MailBranding;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Mailable;

/**
 * Email aktivasi akun AMBARA.
 *
 * Sengaja TIDAK mengimplementasikan ShouldQueue: aplikasi berjalan di Vercel
 * (serverless) tanpa queue worker, sehingga notifikasi berantrean akan
 * menumpuk di tabel `jobs` dan tidak pernah terkirim. Notifikasi dikirim
 * sinkron agar status pengiriman bisa langsung dilaporkan ke pengguna.
 *
 * URL verifikasi diwarisi dari Illuminate\Auth\Notifications\VerifyEmail,
 * yaitu signed URL + hash SHA1 email yang cocok dengan route
 * `verification.verify`.
 *
 * Catatan: method ini mengembalikan Mailable, bukan Illuminate\Notifications\
 * Messages\MailMessage. MailMessage::view() hanya menghasilkan satu part
 * text/html tanpa alternatif teks, sedangkan deliverability ke mailbox tertentu
 * (dan rekomendasi Resend) menuntut bagian text/plain juga.
 */
class AccountActivationNotification extends VerifyEmail
{
    /**
     * Masa berlaku link verifikasi dalam menit.
     */
    public static function expiryMinutes(): int
    {
        $minutes = (int) config('auth.verification.expire', 60);

        return $minutes > 0 ? $minutes : 60;
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): Mailable
    {
        $url = $this->verificationUrl($notifiable);

        $data = [
            'url' => $url,
            'appName' => MailBranding::appName(),
            'ambalanName' => MailBranding::ambalanName(),
            'logoUrl' => MailBranding::logoUrl(),
            'displayName' => $this->displayName($notifiable),
            'username' => $this->attribute($notifiable, 'username', '-'),
            'email' => $this->emailOf($notifiable),
            'expiryMinutes' => self::expiryMinutes(),
        ];

        // MailChannel::send() mengirim Mailable secara langsung lewat
        // $mailable->send() tanpa melewati messageBuilder, sehingga penerima
        // tidak lagi diisi otomatis seperti pada MailMessage. Tanpa baris ini
        // email terkirim tanpa header "To".
        return (new Mailable)
            ->to($notifiable)
            ->subject('Aktivasi Akun '.$data['appName'].' - '.$data['username'])
            ->view('emails.account-activation', $data)
            ->text('emails.account-activation-text', $data);
    }

    private function displayName($notifiable): string
    {
        return $this->attribute($notifiable, 'name', $this->emailOf($notifiable));
    }

    private function emailOf($notifiable): string
    {
        if (is_object($notifiable) && method_exists($notifiable, 'getEmailForVerification')) {
            return (string) $notifiable->getEmailForVerification();
        }

        return '-';
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
