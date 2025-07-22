<?php
namespace  HlsVideos\Traits;

use HlsVideos\Models\HlsVideo;

trait HasHlsVideo
{
    public function hlsVideos()
    {
        return $this->morphToMany(HlsVideo::class, 'videoable','hls_videoables','videoable_id','hls_video_id');
    }

    public function hlsVideo()
    {
        return $this->morphOne(HlsVideo::class,'videoable','hls_videoables','videoable_id','hls_video_id');
    }
}