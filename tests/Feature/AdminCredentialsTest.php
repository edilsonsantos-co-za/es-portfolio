<?php

namespace Tests\Feature;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCredentialsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated in-memory DB so these tests never touch a real database.
        Config::set('database.connections.testing_admins', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        Config::set('database.default', 'testing_admins');
        DB::purge('testing_admins');

        Schema::create('admins', function ($table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        putenv('ADMIN_PASSWORD');
        unset($_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);

        parent::tearDown();
    }

    public function testCheckDefaultFlagsAdminsUsingTheDemoPassword(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('12345')]);

        $this->artisan('admin:password', ['--check-default' => true])
            ->expectsOutputToContain('admin@admin.com is using a default password')
            ->assertFailed();
    }

    public function testCheckDefaultPassesForNonDefaultPasswords(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('a-strong-unique-password')]);

        $this->artisan('admin:password', ['--check-default' => true])->assertSuccessful();
    }

    public function testGeneratedPasswordReplacesTheExistingHash(): void
    {
        $admin = Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('12345')]);

        $this->artisan('admin:password', ['email' => 'admin@admin.com'])
            ->expectsQuestion('New password (leave empty to generate one)', '')
            ->expectsOutputToContain('Generated password:')
            ->assertSuccessful();

        $this->assertFalse(Hash::check('12345', $admin->fresh()->password));
    }

    public function testShortPasswordsAreRejected(): void
    {
        Admin::create(['email' => 'admin@admin.com', 'password' => Hash::make('12345')]);

        $this->artisan('admin:password', ['email' => 'admin@admin.com'])
            ->expectsQuestion('New password (leave empty to generate one)', 'short')
            ->assertFailed();
    }

    public function testProductionSeederRefusesToUseTheDemoPassword(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => AdminSeeder::class, '--force' => true]);

        $this->assertSame(0, Admin::count());
    }
}
