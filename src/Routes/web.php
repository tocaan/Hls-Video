<?php
use Illuminate\Support\Facades\Route;
use HlsVideos\Http\Controllers\HlsVideoController;

Route::name('hls.videos.')
    ->prefix('hls/videos')
    ->middleware(config('hls-videos.uploader_access_middleware'))
    ->group(function () {

    Route::any('upload', [HlsVideoController::class, 'uploadVideo'])->name('upload');
    Route::any('video-options', [HlsVideoController::class, 'getOptions'])->name('options');
});