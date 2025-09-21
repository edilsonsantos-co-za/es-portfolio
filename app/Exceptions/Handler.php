<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Add database debugging information for database-related errors
        if ($this->shouldAddDatabaseDebugInfo($exception)) {
            $debugInfo = $this->getDatabaseDebugInfo($exception);
            view()->share('databaseDebugInfo', $debugInfo);
        }

        return parent::render($request, $exception);
    }

    /**
     * Determine if we should add database debug info
     */
    protected function shouldAddDatabaseDebugInfo(Throwable $exception): bool
    {
        return $exception instanceof QueryException || 
               str_contains($exception->getMessage(), 'database') ||
               str_contains($exception->getMessage(), 'migration') ||
               str_contains($exception->getMessage(), 'propagated');
    }

    /**
     * Get database debugging information
     */
    protected function getDatabaseDebugInfo(Throwable $exception): array
    {
        $debugInfo = [
            'database_config' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'port' => config('database.connections.' . config('database.default') . '.port'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'username' => config('database.connections.' . config('database.default') . '.username'),
            ],
            'connection_test' => null,
            'migration_status' => null,
            'table_count' => null,
        ];

        try {
            // Test database connection
            DB::connection()->getPdo();
            $debugInfo['connection_test'] = 'SUCCESS';
            
            // Get table count
            $tables = DB::select('SHOW TABLES');
            $debugInfo['table_count'] = count($tables);
            
            // Check migration status
            try {
                if (Schema::hasTable('migrations')) {
                    $migrations = DB::table('migrations')->count();
                    $debugInfo['migration_status'] = "Migrations table exists with {$migrations} entries";
                } else {
                    $debugInfo['migration_status'] = 'Migrations table does not exist';
                }
            } catch (\Exception $e) {
                $debugInfo['migration_status'] = 'Failed to check migrations: ' . $e->getMessage();
            }
            
        } catch (\Exception $e) {
            $debugInfo['connection_test'] = 'FAILED: ' . $e->getMessage();
        }

        return $debugInfo;
    }
}
