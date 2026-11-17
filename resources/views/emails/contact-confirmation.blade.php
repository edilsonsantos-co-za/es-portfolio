@extends('emails.layout', ['eyebrow' => 'Message received', 'title' => 'Thanks for getting in touch', 'preheader' => 'Your message reached me. I will reply to this address.'])
@section('body')
<p style="margin:0 0 16px;">Hi {{ $contact->name }},</p>
<p style="margin:0 0 16px;">Your message about <strong>{{ $contact->subject }}</strong> reached me. I read every one and will reply to this address, usually within two working days.</p>
<p style="margin:0;font-size:13px;line-height:1.6;color:#6E6E79;">You are getting this because this address was entered in the contact form on edilsonsantos.co.za. If that wasn't you, ignore this email; nothing else will be sent.</p>
@endsection
