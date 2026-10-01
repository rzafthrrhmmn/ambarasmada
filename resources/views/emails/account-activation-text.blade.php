{{-- Versi teks polos (text/plain) dari email aktivasi akun.

     Disertakan sebagai alternatif multipart/alternative agar email tetap
     terbaca pada mail client yang tidak mendukung HTML, dan agar pengirim
     tetap mendapat reputasi baik (rekomendasi deliverability Resend).

     Jangan gunakan tag HTML apa pun di sini. --}}
{{ $appName }} - AKTIVASI AKUN
{{ str_repeat('=', 40) }}

Halo {{ $displayName }}!

Akun Anda di {{ $appName }} berhasil dibuat dengan detail berikut:

  Nama   : {{ $displayName }}
  NTA    : {{ $username }}
  Email  : {{ $email }}

{{ str_repeat('-', 40) }}

AKUN ANDA BELUM AKTIF

Akun aktif setelah alamat email diverifikasi. Gunakan tautan di bawah ini
untuk memverifikasi alamat email Anda:

{!! $url !!}

Salin tautan tersebut ke browser Anda bila tautannya tidak dapat diklik.

{{ str_repeat('-', 40) }}

CATATAN PENTING

1. Sistem meminta Anda masuk (login) terlebih dahulu sebelum aktivasi dapat
   diselesaikan. Setelah berhasil masuk, Anda akan otomatis diarahkan ke
   halaman aktivasi sehingga akun langsung aktif.

2. Tautan ini berlaku selama {{ $expiryMinutes }} menit. Bila sudah
   kedaluwarsa, buka aplikasi lalu tekan "Kirim Ulang Verifikasi" pada halaman
   aktivasi, atau gunakan tombol "Kirim Ulang Email Aktivasi" di halaman masuk.

3. Jika Anda tidak mendaftar akun ini, abaikan saja email ini.

{{ str_repeat('=', 40) }}

Salam,
Admin {{ $appName }}
{{ $ambalanName }}

Email ini dikirim otomatis. Mohon tidak membalas pesan ini.