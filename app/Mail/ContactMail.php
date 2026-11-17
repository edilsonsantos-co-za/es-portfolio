<?php

namespace App\Mail;

use App\Models\Admin;
use App\Models\About;
use App\Models\Message;
use App\Models\MessageReply;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The three emails of the contact form, built from one class so they share the sender, layout and
 * failure handling:
 *
 *  - confirmation: to the visitor, "your message arrived";
 *  - notification: to the owner, the full message, Reply-To set to the visitor;
 *  - reply:        to the visitor, the owner's answer from Admin > Messages, Reply-To set to the owner.
 *
 * Sending never throws into the caller: the contact form must not fail because mail is down. Each
 * send returns true or false and logs the reason.
 */
class ContactMail extends Mailable
{
    public function __construct(
        public string $kind,
        public Message $contact,
        public ?MessageReply $reply = null,
        public ?string $ownerEmail = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->kind) {
            'confirmation' => 'I received your message',
            'notification' => 'New message: '.$this->contact->subject,
            'reply' => 'Re: '.$this->contact->subject,
        };

        $replyTo = match ($this->kind) {
            'notification' => [new Address($this->contact->email, $this->contact->name)],
            'reply' => $this->ownerEmail ? [new Address($this->ownerEmail)] : [],
            default => [],
        };

        return new Envelope(subject: $subject, replyTo: $replyTo);
    }

    public function content(): Content
    {
        return new Content(
            view: "emails.contact-{$this->kind}",
            text: "emails.contact-{$this->kind}-text",
            with: ['adminUrl' => url('/admin/messages')],
        );
    }

    /** Where notifications go and what replies offer as Reply-To: the admin's login email, else the public one. */
    public static function ownerEmail(): ?string
    {
        return optional(Admin::query()->orderBy('id')->first())->email
            ?: optional(About::query()->first())->email;
    }

    /** After a visitor submits the form: confirmation to them, notification to the owner. */
    public static function received(Message $contact): array
    {
        $owner = self::ownerEmail();

        return [
            'confirmation' => self::deliver($contact->email, new self('confirmation', $contact)),
            'notification' => $owner ? self::deliver($owner, new self('notification', $contact)) : false,
        ];
    }

    /** The owner's reply from the admin panel. */
    public static function reply(Message $contact, MessageReply $reply): bool
    {
        return self::deliver($contact->email, new self('reply', $contact, $reply, self::ownerEmail()));
    }

    private static function deliver(string $to, self $mail): bool
    {
        try {
            Mail::to($to)->send($mail);
            return true;
        } catch (\Throwable $th) {
            Log::error("Contact mail '{$mail->kind}' for message {$mail->contact->id} failed: ".$th->getMessage());
            return false;
        }
    }
}
