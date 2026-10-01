<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>@yield('emailTitle', $appName)</title>
</head>
<body style="margin:0;padding:0;background-color:#263D26;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#263D26;">
<tr>
<td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#335233;border:2px solid #A7B92A;border-radius:16px;overflow:hidden;">

<tr>
<td align="center" style="padding:28px 32px 8px;background-color:#263D26;">
<img src="{{ $logoUrl }}" alt="Logo {{ $ambalanName }}" width="80" height="80" style="display:block;margin:0 auto;border-radius:16px;background-color:#263D26;border:2px solid #6F9435;">
<p style="margin:14px 0 0;font-size:14px;font-weight:700;line-height:20px;color:#EDD330;text-align:center;">{{ $appName }}</p>
</td>
</tr>

<tr>
<td style="padding:20px 32px 28px;">
@yield('content')
</td>
</tr>

<tr>
<td style="padding:18px 32px;background-color:#263D26;border-top:1px solid #6F9435;">
<p style="margin:0;font-size:12px;line-height:18px;color:#8fa06a;text-align:center;">
{{ $ambalanName }}<br>
Email ini dikirim otomatis. Mohon tidak membalas pesan ini.
</p>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>