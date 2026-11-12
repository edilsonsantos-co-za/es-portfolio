@extends('emails.layout', ['eyebrow' => 'Admin', 'title' => 'Reset your password', 'preheader' => 'A password reset was requested for your ES Portfolio admin account.'])
@section('body')
<p style="margin:0 0 16px;">Someone asked to reset the password for your ES Portfolio admin account. Choose a new one here:</p>
@include('emails.button', ['href' => $link, 'label' => 'Choose a new password'])
<p style="margin:0;font-size:13px;line-height:1.6;color:#6E6E79;">The link expires in {{ $minutes }} minutes. If you didn't ask for this, ignore this email: your password stays the same.</p>
@endsection
