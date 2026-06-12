<?php

use App\Services\ApplicationVersion;
use Illuminate\Support\Facades\Route;

//log viewer
Route::middleware('operational.web')->group(function () {
    Route::get('/admin/system-logs', ['\Rap2hpoutre\LaravelLogViewer\LogViewerController', 'index'])
        ->name('admin.system-logs');

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

Route::group(['prefix' => 'admin'], function () {
    Route::get('/{path?}', ['App\Http\Controllers\Admin\AdminController', 'app'])->where('path', '.*')->name('admin.app');
});


#region [frontend]

Route::get('/', ['App\Http\Controllers\Frontend\FrontendController', 'index'])->name('frontend');
Route::get('/pixel-tracker', ['App\Http\Controllers\Frontend\FrontendController', 'pixelTracker'])->name('pixel-tracker');

#endregion
