# HLS Videos

Laravel package for uploading videos, converting them to HLS-ready streams, and rendering an HLS player in Blade.

## Installation

This package is not published on Packagist yet, so add the GitHub repository to your Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/tocaan/Hls-Video.git"
        }
    ]
}
```

Then install the package:

```bash
composer require tocaan/hls-videos
```

Publish the package config:

```bash
php artisan vendor:publish --provider="HlsVideos\Providers\HlsVideoServiceProvider" --tag="config"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

After publishing, update `config/hls-videos.php` as needed.

The player builds stream URLs from the route name in `access_route_stream`, so set it to the route name you add in your app:

```php
'access_route_stream' => 'api.video.stream',
```

Configure the related disks and URL in your `.env`:

```env
HLS_VIDEO_TEMP_DISK=temp_video
HLS_VIDEO_THUMB_DISK=thumbnails
HLS_VIDEO_STREAM_DISK=r2
HLS_VIDEO_STREAM_DISK_URL=https://example.com/path-to-stream-storage
HLS_VIDEO_SEGMENT_LENGTH=4
```

Make sure these disks exist in your Laravel `config/filesystems.php`. `temp_disk`
must use the `local` driver — conversion calls `->path()` and `mkdir()` on it,
which throw on S3-compatible adapters.

`stream_disk_url` is the public base URL of `stream_disk`. It is substituted
into playlists when they are served, so players fetch `.ts` segments straight
from storage. Leave it unset and every segment is proxied through PHP instead.

### Conversion Queue

`ConvertQualityJob` is dispatched onto `queue_connection` / `queue`. Give it a
connection of its own — a single transcode occupies a worker for minutes, and
on a shared queue it blocks mail and notifications behind it.

```env
HLS_VIDEO_QUEUE_CONNECTION=redis-video
HLS_VIDEO_QUEUE=video
HLS_VIDEO_JOB_TIMEOUT=7200
```

**That connection's `retry_after` must exceed both the longest expected
conversion and `job_timeout`.** Redis releases a job's reservation once
`retry_after` elapses and hands it to a second worker, and the conversion
service empties its output folder when it starts, so two concurrent runs
overwrite each other's segments.

Conversion reads the source video as a local absolute path, so the queue worker
must share a filesystem with whatever received the upload.

### Quality Tiers

Each `qualities` array key must equal its own `quality` value — the processor
factory looks entries up by quality name. Tiers convert one at a time, in
order, each in its own job, and the video is not marked `ready` until the last
one finishes.

`FfmpegService` understands the names `1080`, `720`, `480` and `360`. Any other
name must carry explicit `width`, `height` and `kbps`, or conversion throws:

```php
'qualities' => [
    'hd' => [
        'quality' => 'hd',
        'convert_service' => FfmpegService::class,
        'width' => 1600, 'height' => 900, 'kbps' => 2000,
    ],
],
```

`FfmpegService` runs on your own server. The alternative, `Mp4ToService`,
uploads the source video to the third-party mp4.to service and needs
`HLS_VIDEO_MP4_TO_TOKEN`.

Binary paths come from `pbmedia/laravel-ffmpeg`'s own `config/laravel-ffmpeg.php`
(`FFMPEG_BINARIES` / `FFPROBE_BINARIES`). Set them explicitly — a queue
worker's `PATH` rarely matches an interactive shell's.

### Attachable Models

`videoable_models` is an allowlist of models a video may be attached to, as
`alias => class`. The upload endpoint validates `model_type` against it, so
until you populate it no model can be attached:

```php
'videoable_models' => [
    'lesson' => \App\Models\Lesson::class,
],
```

### Optional Steps

| Key | Default | Effect |
| --- | --- | --- |
| `take_thumbnail` | `false` | Grabs a frame at 3s onto `thumb_disk`. |
| `support_compress` | `false` | Writes an extra `vd.zip` of every segment for offline download, roughly doubling storage per video. |
| `ignored_domains` | `[]` | Rewritten to `app.url` when serving a playlist, for apps that changed domain after converting. |

## Layout Stacks

The video manager component pushes its CSS and JavaScript into Blade stacks. Add these stacks to the layout that renders the package components:

```blade
<head>
    @stack('hls-styles')
</head>
<body>
    {{-- page content --}}

    @stack('hls-scripts')
</body>
```

## Model Setup

Add the `HasHlsVideo` trait to any model that should own a video:

```php
<?php

namespace App\Models;

use HlsVideos\Traits\HasHlsVideo;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasHlsVideo;
}
```

## Upload And Manage Videos

Use the video manager component when you want to upload, replace, delete, or show the current video for a model.

```blade
<x-hls-video-manager :model="$course" />
```

The model passed to this component must use `HlsVideos\Traits\HasHlsVideo`.

## Play Videos

Use the package player components where you want to display an HLS video.

```blade
<x-hls-play />
<x-hls-play-js />

<script>
    playHls('{{ $course->getReadyHlsVideo()?->id }}', '{{ $course->getReadyHlsVideo()?->thumb_url }}');
</script>
```

You can also use the video URL generated by the model:

```blade
@if ($video = $course->getReadyHlsVideo())
    <video controls>
        <source src="{{ $video->video_link }}" type="application/x-mpegURL">
    </video>
@endif
```

## Stream Route

Add a stream route to your Laravel app. The route name must match `access_route_stream` in `config/hls-videos.php`.

```php
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/vd/{id}/stream/{quality?}/{fileName?}', [VideoController::class, 'stream'])
    ->name('api.video.stream');
```

Then add the controller method:

```php
<?php

namespace App\Http\Controllers;

use HlsVideos\Services\VideoService;

class VideoController extends Controller
{
    public function stream($id, $quality = null, $file = null)
    {
        return VideoService::getStreamTemporaryLink($id, $quality, $file);
    }
}
```

## Package Routes

The package registers the bundled uploader's endpoints under the `hls/videos`
prefix, protected by `uploader_access_middleware`. They are loaded with
`loadRoutesFrom()` and get no middleware group of their own, so `web` has to be
in that list for the session and CSRF token the uploader sends.

| Route name | Method | Path |
| --- | --- | --- |
| `hls.videos.list` | GET | `hls/videos/list` |
| `hls.videos.upload` | ANY | `hls/videos/upload` |
| `hls.videos.options` | GET | `hls/videos/video-options/{videoId?}` |
| `hls.videos.delete` | DELETE | `hls/videos/video-delete/{videoId}` |

`hls.videos.upload` accepts a chunked `file` (via `pion/laravel-chunk-upload`)
plus an optional `model_type` / `model_id` pair, where `model_type` is an alias
or class-string from `videoable_models`. It returns the same JSON shape as
`hls.videos.options`: `html`, `build_uploader`, `is_ready`, `video_source`,
`video_id`.

The bundled Blade uploader needs jQuery, bootbox, toastr and CDN copies of Uppy
and Plyr, which do not exist in a Filament or Livewire admin panel. If you are
building your own uploader, either target those four endpoints or skip them
entirely — set `register_routes` to false and call `VideoService` directly:

```php
app(\HlsVideos\Services\VideoService::class)
    ->handlingUploadedFile($uploadedFile, $lesson, deleteChunked: false);
```
