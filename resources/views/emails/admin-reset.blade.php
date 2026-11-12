@extends('emails.layout', ['eyebrow' => 'Admin', 'title' => 'Reset your password', 'preheader' => 'A password reset was requested for your ES Portfolio admin account.'])
@section('body')
<p style="margin:0 0 16px;">Someone asked to reset the password for your ES Portfolio admin account. Choose a new one here:</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 24px;"><tr><td style="background:#2563EB;border-radius:999px;"><a href="{{ $link }}" style="display:inline-block;padding:14px 26px;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:999px;">Choose a new password</a></td></tr></table>
<p style="margin:0;font-size:13px;line-height:1.6;color:#6E6E79;">The link expires in {{ $minutes }} minutes. If you didn't ask for this, ignore this email: your password stays the same.</p>
@endsection
