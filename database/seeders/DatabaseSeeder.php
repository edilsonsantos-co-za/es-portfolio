<?php

namespace Database\Seeders;

use Artisan;
use DB;
use Illuminate\Database\Seeder;
use Log;
use Schema;
use Session;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        try {
            try {
                // clear log
                file_put_contents(storage_path('logs/laravel.log'), '');
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate all tables, except migrations
            $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
            foreach ($tables as $table) {
                if ($table !== 'migrations') {
                    DB::table($table)->truncate();
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->call(AdminSeeder::class);
            $this->call(PortfolioSeeder::class);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}
