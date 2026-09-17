<?php

namespace  HlsVideos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use  HlsVideos\Services\VideoService;

/**
 * @property string $id UUID; also the folder name on both the temp and stream disks.
 * @property string $status One of UPLOADED, PROCESSING, READY.
 * @property string|null $file_name
 * @property string|null $original_extension
 * @property string|null $original_file_name
 * @property array|null $stream_data
 * @property-read string $video_link
 * @property-read string $thumb_url
 * @property-read bool $is_ready
 * @property-read string|null $temp_video Absolute local path, or null once cleaned up.
 * @property-read string|null $temp_video_folder
 * @property-read string $temp_video_path
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HlsVideoQuality> $qualities
 */
class HlsVideo extends Model
{

    const UPLOADED = 'uploaded';
    const PROCESSING = 'processing';
    const READY = 'ready';
    protected $guarded = [];
    protected $casts = ['stream_data' => 'array'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::created(function ($video) {
            $videoService = new VideoService;
            $videoService->createThumb($video);
            $videoService->handleVideoQualities($video);
        });

        static::deleting(function ($video) {
            $video->qualities()->delete();
            foreach(config('hls-videos.storages') as $disk => $config){
                Storage::disk($disk)->deleteDirectory($video->id);
            }
        });
    }

    public function qualities(){
        return $this->hasMany(HlsVideoQuality::class,'hls_video_id');
    }

    /**
     * There is deliberately no inverse of HasHlsVideo::hlsVideos() here.
     * morphedByMany() takes one class-string, so a single relation cannot
     * span every videoable type; query `hls_videoables` from the owning side
     * instead.
     */
    public function scopeReady($query){
        return $query->where('status', self::READY);
    }

    public function getThumbUrlAttribute(){

        $thumbPath = "$this->id/thumb.jpg";
        return Storage::disk(config('hls-videos.thumb_disk'))->url($thumbPath);
    }

    public function getTempVideoAttribute(){

        $path = "{$this->id}/{$this->file_name}";
        return Storage::disk(config('hls-videos.temp_disk'))->exists($path) ? Storage::disk(config('hls-videos.temp_disk'))->path($path) : null;
    }

    public function getTempVideoFolderAttribute(){

        $path = "{$this->id}";
        return Storage::disk(config('hls-videos.temp_disk'))->exists($path) ? Storage::disk(config('hls-videos.temp_disk'))->path($path) : null;
    }

    public function getTempVideoPathAttribute(){

        return "{$this->id}/{$this->file_name}";
    }

    public function getIsReadyAttribute(){

        return $this->status == self::READY;
    }

    public function getVideoLinkAttribute(){

        return route(config('hls-videos.access_route_stream'),[$this->id]);
    }
}
