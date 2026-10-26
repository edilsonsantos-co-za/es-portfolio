<?php

use App\Services\ApplicationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('operational.web')->group(function () {
    Route::get('/optimize', ['App\Http\Controllers\Admin\AdminController', 'optimize'])->name('optimize');
});

Route::get('/health', function (ApplicationVersion $applicationVersion) {
    return response()->json([
        'status' => 'ok',
        'version' => $applicationVersion->version(),
        'release_tag' => $applicationVersion->releaseTag(),
        'environment' => app()->environment(),
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');

Route::get('/ready', function (ApplicationVersion $applicationVersion) {
    try {
        DB::select('select 1');
    } catch (\Throwable $exception) {
        report($exception);

        return response()->json([
            'status' => 'unavailable',
            'version' => $applicationVersion->version(),
            'release_tag' => $applicationVersion->releaseTag(),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
        ], 503);
    }

    return response()->json([
        'status' => 'ready',
        'version' => $applicationVersion->version(),
        'release_tag' => $applicationVersion->releaseTag(),
        'environment' => app()->environment(),
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('ready');

Route::get('/admin/system-logs', function () {
    abort(404);
});

Route::get('/auth/sso/login', ['App\Http\Controllers\Auth\SsoController', 'login'])->name('sso.login');
Route::get('/auth/sso/callback', ['App\Http\Controllers\Auth\SsoController', 'callback'])->name('sso.callback');

Route::group(['prefix' => 'admin'], function () {
    Route::get('/{path?}', ['App\Http\Controllers\Admin\AdminController', 'app'])->where('path', '.*')->name('admin.app');
});


#region [frontend]

Route::get('/', ['App\Http\Controllers\Frontend\FrontendController', 'index'])->name('frontend');
Route::get('/pixel-tracker', ['App\Http\Controllers\Frontend\FrontendController', 'pixelTracker'])->name('pixel-tracker');

#endregion
