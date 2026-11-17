@extends('emails.layout', ['eyebrow' => 'Reply', 'title' => 'Re: '.$contact->subject, 'preheader' => \Illuminate\Support\Str::limit($reply->body, 90)])
@section('body')
<div style="margin:0 0 24px;white-space:pre-line;">{{ $reply->body }}</div>
<p style="margin:0 0 8px;font-size:13px;color:#6E6E79;">Your message of {{ $contact->created_at->format('j F Y') }}:</p>
<div style="margin:0 0 20px;padding:14px 16px;border-left:3px solid #DCE3EE;color:#6E6E79;font-size:14px;white-space:pre-line;">{{ $contact->body }}</div>
@if ($ownerEmail)
<p style="margin:0;font-size:13px;line-height:1.6;color:#6E6E79;">Reply to this email to continue the conversation.</p>
@endif
@endsection
