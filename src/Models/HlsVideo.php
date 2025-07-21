<?php

namespace  HlsVideos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use  HlsVideos\Services\VideoService;

class HlsVideo extends Model
{

    const UPLOADED = 'uploaded';
    const PROCESSING = 'processing';
    const READY = 'ready';
    protected $guarded = [];
    public $casts = ['stream_data' => 'array'];
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
    }

    public function qualities(){
        return $this->hasMany(HlsVideoQuality::class,'hls_video_id');
    }

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
}
