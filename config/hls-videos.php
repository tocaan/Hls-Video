<?php

use HlsVideos\Services\Qualities\FfmpegService;
use HlsVideos\Services\Storages\R2StorageService;

return [

    /*
    |--------------------------------------------------------------------------
    | Stream Route
    |--------------------------------------------------------------------------
    |
    | Name of the route in your application that serves playlists and
    | segments. Its URL is baked into every generated playlist at conversion
    | time, so renaming it invalidates already-converted videos.
    |
    | The route must accept: {video}, {quality?}, {file?}.
    |
    */

    'access_route_stream' => 'dashboard.video.stream',

    /*
    |--------------------------------------------------------------------------
    | Bundled Uploader Routes
    |--------------------------------------------------------------------------
    |
    | The package registers upload/list/options/delete endpoints under the
    | `hls/videos` prefix. Set `register_routes` to false if your app drives
    | uploads through its own controllers and should not expose these.
    |
    | `uploader_access_middleware` is what stands between the internet and an
    | endpoint that writes files and creates records, so it must never be
    | empty. The routes are loaded with loadRoutesFrom() and get no middleware
    | group of their own, which is why `web` is listed here — the bundled
    | uploader needs the session and CSRF token.
    |
    */

    'register_routes' => true,
    'uploader_access_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Attachable Models
    |--------------------------------------------------------------------------
    |
    | Allowlist of models a video may be attached to, as alias => class. The
    | upload request's `model_type` is validated against this, so leaving it
    | empty means no model can be attached through the bundled endpoint.
    |
    */

    'videoable_models' => [
        // 'lesson' => \App\Models\Lesson::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Disks
    |--------------------------------------------------------------------------
    |
    | `temp_disk` MUST be a local driver: conversion calls ->path() and mkdir()
    | on it. `stream_disk` is where finished playlists and segments live, and
    | `stream_disk_url` is that bucket's public base URL — it is substituted
    | into playlists so players fetch segments directly from storage instead
    | of proxying every one of them through PHP.
    |
    */

    'temp_disk' => env("HLS_VIDEO_TEMP_DISK", 'temp_video'),
    'thumb_disk' => env("HLS_VIDEO_THUMB_DISK", 'thumbnails'),
    'stream_disk' => env("HLS_VIDEO_STREAM_DISK", 'r2'),
    'stream_disk_url' => env("HLS_VIDEO_STREAM_DISK_URL"),

    /*
    |--------------------------------------------------------------------------
    | Conversion Queue
    |--------------------------------------------------------------------------
    |
    | ConvertQualityJob is dispatched onto this connection and queue. Keep it
    | off the connection the rest of your app drains: a single transcode can
    | occupy a worker for many minutes.
    |
    | The connection's `retry_after` MUST exceed the longest expected
    | conversion (and `job_timeout` below). Redis releases a job's reservation
    | once `retry_after` elapses and hands it to a second worker, and the
    | conversion service empties its output folder when it starts — so two
    | concurrent runs destroy each other's segments.
    |
    | `job_timeout` is the job's own wall-clock limit in seconds. 0 means no
    | limit, which lets a wedged ffmpeg hold its worker indefinitely.
    |
    */

    'queue_connection' => env("HLS_VIDEO_QUEUE_CONNECTION"),
    'queue' => env("HLS_VIDEO_QUEUE", 'default'),
    'job_timeout' => (int) env("HLS_VIDEO_JOB_TIMEOUT", 7200),

    /*
    |--------------------------------------------------------------------------
    | Optional Pipeline Steps
    |--------------------------------------------------------------------------
    |
    | `take_thumbnail` grabs a frame at 3s onto `thumb_disk`.
    |
    | `support_compress` writes an extra `vd.zip` of every segment next to the
    | stream once the last quality is done, for offline download. It doubles
    | the storage per video and is off unless you need that feature.
    |
    | `ignored_domains` are rewritten to `app.url` when serving a playlist,
    | for apps that moved domains after converting videos.
    |
    */

    'take_thumbnail' => env("HLS_VIDEO_TAKE_THUMBNAIL", false),
    'support_compress' => env("HLS_VIDEO_SUPPORT_COMPRESS", false),
    'ignored_domains' => [],

    /*
    |--------------------------------------------------------------------------
    | HLS Segment Length
    |--------------------------------------------------------------------------
    */

    'segment_length' => (int) env("HLS_VIDEO_SEGMENT_LENGTH", 6),

    /*
    |--------------------------------------------------------------------------
    | Storages
    |--------------------------------------------------------------------------
    |
    | Where converted output is pushed. Each array KEY must be a real disk
    | name in config/filesystems.php — it is used directly as
    | Storage::disk($key) when clearing folders and deleting videos.
    |
    */

    'storages' => [
        'r2' => [
            'disk_name' => 'r2',
            'service' => R2StorageService::class
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Tiers
    |--------------------------------------------------------------------------
    |
    | Each array KEY must equal its own `quality` value: the processor factory
    | looks the entry up by quality name. Tiers are converted one at a time,
    | in order, each in its own queued job.
    |
    | FfmpegService understands the names 1080, 720, 480 and 360. Any other
    | name must carry explicit `width`, `height` and `kbps`:
    |
    |   'hd' => [
    |       'quality' => 'hd',
    |       'convert_service' => FfmpegService::class,
    |       'width' => 1600, 'height' => 900, 'kbps' => 2000,
    |   ],
    |
    | Mp4ToService is also available, but it uploads the source video to the
    | third-party mp4.to service for conversion. FfmpegService runs entirely
    | on your own server, which is why it is the default here.
    |
    */

    'qualities' => [
        '720' => [
            'quality' => '720',
            'convert_service' => FfmpegService::class
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | mp4.to API Token
    |--------------------------------------------------------------------------
    |
    | Only needed if a quality tier uses Mp4ToService.
    |
    */

    'mp4_to_token' => env("HLS_VIDEO_MP4_TO_TOKEN"),
];
