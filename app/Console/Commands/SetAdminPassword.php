<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetAdminPassword extends Command
{
    /**
     * Passwords shipped by the demo seeders; never acceptable in production.
     */
    public const DEFAULT_PASSWORDS = ['12345'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:password
                            {email? : Admin email to update}
                            {--check-default : Exit non-zero if any admin still uses a default password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set an admin password (prompted or generated), or audit for default passwords';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('check-default')) {
            return $this->checkDefaultPasswords();
        }

        $email = $this->argument('email');

        if (empty($email)) {
            $this->error('An admin email is required.');

            return self::FAILURE;
        }

        $admin = Admin::where('email', $email)->first();

        if (!$admin) {
            $this->error("No admin found for {$email}.");

            return self::FAILURE;
        }

        $password = $this->secret('New password (leave empty to generate one)');
        $generated = empty($password);

        if ($generated) {
            $password = Str::password(24, symbols: false);
        } elseif (strlen($password) < 12 || in_array($password, self::DEFAULT_PASSWORDS, true)) {
            $this->error('Password must be at least 12 characters and not a default password.');

            return self::FAILURE;
        } elseif ($this->secret('Confirm password') !== $password) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $admin->password = Hash::make($password);
        $admin->save();

        $this->info("Password updated for {$email}.");

        if ($generated) {
            $this->line("Generated password: {$password}");
        }

        return self::SUCCESS;
    }

    private function checkDefaultPasswords(): int
    {
        $flagged = Admin::all(['id', 'email', 'password'])->filter(function (Admin $admin) {
            foreach (self::DEFAULT_PASSWORDS as $default) {
                if (Hash::check($default, $admin->password)) {
                    return true;
                }
            }

            return false;
        });

        if ($flagged->isEmpty()) {
            $this->info('No admin accounts use a default password.');

            return self::SUCCESS;
        }

        foreach ($flagged as $admin) {
            $this->warn("Admin {$admin->email} is using a default password.");
        }

        return self::FAILURE;
    }
}
