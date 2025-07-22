<?php

namespace  HlsVideos\Services\Qualities;

use  HlsVideos\DTOS\VideoConverted;
use  HlsVideos\Models\HlsVideoQuality;
use  HlsVideos\Services\Contracts\VideoQualityProcessorInterface;
use FFMpeg;

class FfmpegService implements VideoQualityProcessorInterface
{
    protected $quality;
    protected $video;
    protected $headers;


    public function convertVideo($videoFile, HlsVideoQuality $quality): VideoConverted{
        
        $this->video = $quality->video;
        $this->quality = $quality;
        [$width, $height, $videoKbps] = $this->getQualitySettings($this->quality->quality);

        FFMpeg::fromDisk(config('hls-videos.temp_disk'))
        ->open($this->video->temp_video_path)
        ->exportForHLS()
        ->setSegmentLength(4) // seconds
        ->setKeyFrameInterval(48) // for better seeking performance
        ->addFormat(
            FFMpeg::hls()
            ->setKiloBitrate($videoKbps)
            ->setAudioKiloBitrate(96)
            ->scale($width, $height)
        )
        ->toDisk(config('hls-videos.temp_disk')) // Output disk (can be S3, local, etc.)
        ->save("{$this->video->id}/{$this->quality->quality}/vd.m3u8");
    }


    protected function getQualitySettings($quality)
    {
        // Width, Height, Video Bitrate in Kbps
        return match ($quality) {
            '1080' => [1920, 1080, 5000],
            '720'  => [1280, 720, 2800],
            '480'  => [854, 480, 1400],
            '360'  => [640, 360, 1000],
            '144'  => [256, 144, 300],
            default => [640, 360, 1000],
        };
    }
}
