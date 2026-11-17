Re: {{ $contact->subject }}

{{ $reply->body }}

---
Your message of {{ $contact->created_at->format('j F Y') }}:
{{ $contact->body }}
