<?php

namespace App\Console\Commands;

use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Sends every email the portfolio sends, with sample data, for review in Mailpit
 * (`console mail:gallery es-portfolio` in the workspace runs it).
 */
class MailGallery extends Command
{
    protected $signature = 'mail:gallery {--to=gallery+es-portfolio@datasparklab.local : Recipient}';

    protected $description = 'Send every portfolio email with sample data, for review in Mailpit';

    public function handle(): int
    {
        $to = (string) $this->option('to');
        Notification::route('mail', $to)->notifyNow(new AdminResetPasswordNotification(str_repeat('0', 64)));
        $this->line('  Admin password reset       Reset Password Notification');
        $this->line('  1 sent (the contact form stores messages and sends no email)');

        return self::SUCCESS;
    }
}
