New message from {{ $contact->name }}

From: {{ $contact->name }} <{{ $contact->email }}>
Subject: {{ $contact->subject }}

{{ $contact->body }}

Open Messages: {{ $adminUrl }}
Replying to this email writes to {{ $contact->email }} directly.
