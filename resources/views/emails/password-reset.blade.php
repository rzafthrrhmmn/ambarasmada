@extends('emails.layout')

@section('emailTitle', 'Reset Password '.$appName)

@section('content')
<p style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#A7B92A;">Password Baru</p>
<h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:32px;color:#f0ead8;">Halo {{ $displayName }}!</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#d4dc9a;">Kami menerima permintaan untuk mengatur ulang password akun Anda di {{ $appName }}.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;background-color:#263D26;border:1px solid #6F9435;border-radius:10px;">
<tr>
<td style="padding:14px 16px;font-size:14px;line-height:22px;color:#f0ead8;">
<strong style="color:#EDD330;">Nama</strong><br>{{ $displayName }}<br>
<strong style="color:#EDD330;">NTA</strong><br>{{ $username }}<br>
<strong style="color:#EDD330;">Email</strong><br>{{ $email }}
</td>
</tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 20px;">
<tr>
<td align="center" style="background-color:#EDD330;border:2px solid #EDD330;border-radius:8px;">
<a href="{{ $url }}" style="display:block;padding:14px 32px;font-size:15px;font-weight:800;line-height:20px;color:#263D26;text-decoration:none;">Atur Password Baru</a>
</td>
</tr>
</table>

<p style="margin:0 0 6px;font-size:14px;line-height:22px;color:#d4dc9a;">Jika tombol tidak bereaksi, salin tautan berikut ke browser Anda:</p>
<p style="margin:0 0 20px;font-size:13px;line-height:20px;word-break:break-all;color:#A7B92A;"><a href="{{ $url }}" style="color:#A7B92A;">{{ $url }}</a></p>

<p style="margin:0 0 8px;padding:12px 14px;background-color:#263D26;border-left:3px solid #EDD330;border-radius:6px;font-size:13px;line-height:20px;color:#8fa06a;">Tautan ini berlaku selama <strong style="color:#EDD330;">{{ $expiryMinutes }} menit</strong> dan hanya dapat dipakai satu kali. Bila kedaluwarsa, buka lagi halaman "Lupa Sandi" untuk meminta tautan baru.</p>
<p style="margin:0 0 8px;font-size:13px;line-height:20px;color:#8fa06a;">Pastikan password baru berbeda dari password lama dan tidak mudah ditebak.</p>
<p style="margin:0;font-size:13px;line-height:20px;color:#8fa06a;">Jika Anda tidak meminta reset password ini, abaikan saja email ini. Password Anda tetap aman dan tidak berubah.</p>

<p style="margin:24px 0 0;font-size:14px;line-height:20px;color:#d4dc9a;">Salam,<br><strong style="color:#f0ead8;">Admin {{ $appName }}</strong></p>
@endsection