@extends('emails.layout', ['eyebrow' => 'Contact form', 'title' => 'New message from '.$contact->name, 'preheader' => $contact->subject])
@section('body')
<p style="margin:0 0 6px;"><strong>From:</strong> {{ $contact->name }} &lt;{{ $contact->email }}&gt;</p>
<p style="margin:0 0 18px;"><strong>Subject:</strong> {{ $contact->subject }}</p>
<div style="margin:0 0 24px;padding:16px 18px;background:#F4F6FA;border-radius:12px;white-space:pre-line;">{{ $contact->body }}</div>
@include('emails.button', ['href' => $adminUrl, 'label' => 'Open Messages'])
<p style="margin:0;font-size:13px;line-height:1.6;color:#6E6E79;">Replying to this email writes to {{ $contact->email }} directly. Replying from Messages also records the reply there and marks the message as replied.</p>
@endsection
