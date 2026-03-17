<?php

namespace HlsVideos\Services\Qualities;

use HlsVideos\DTOS\VideoConverted;
use HlsVideos\Models\HlsVideoQuality;
use HlsVideos\Services\Contracts\VideoQualityProcessorInterface;
use FFMpeg;
use FFMpeg\Format\Video\X264;

class FfmpegService implements VideoQualityProcessorInterface
{
    protected $quality;
    protected $video;
    protected $headers;


    public function convertVideo($videoFile, HlsVideoQuality $quality): VideoConverted
    {

        $this->video = $quality->video;
        $this->quality = $quality;

        // Get the actual video dimensions first
        [$actualWidth, $actualHeight] = $this->getActualVideoDimensions();

        [$width, $height, $videoKbps] = $this->getQualitySettings($this->quality->quality, $actualWidth, $actualHeight);
        $bandwidth = $videoKbps * 1024;

        // By default, empty the target quality folder (remove all files but keep the folder)
        $qualityFolder = "{$this->video->id}/{$this->quality->quality}";
        $disk = \Storage::disk(config('hls-videos.temp_disk'));
        if ($disk->exists($qualityFolder)) {
            $files = $disk->files($qualityFolder);
            foreach ($files as $file) {
                $disk->delete($file);
            }
        }

        // Create format
        $format = (new X264)->setKiloBitrate($videoKbps)
            ->setAdditionalParameters([
                '-preset', 'slow',       // better compression (default is 'medium' or 'veryfast')
                '-crf', '23',            // quality-based fallback
                '-profile:v', 'main',
                '-level', '3.1',
            ]);

        FFMpeg::fromDisk(config('hls-videos.temp_disk'))
            ->open($this->video->temp_video_path)
            ->exportForHLS()
            ->setSegmentLength(6) // seconds
            ->setKeyFrameInterval(150) // for better seeking performance
            ->addFormat($format, function ($media) use ($width, $height) {
                $media->scale($width, $height);
            })
            ->useSegmentFilenameGenerator(function ($name, $format, $key, callable $segments, callable $playlist) {
                $segments("{$name}-{$format->getKiloBitrate()}-{$key}-%03d.ts");
                $playlist("{$this->video->id}/{$this->quality->quality}/vd.m3u8");
            })
            ->toDisk(config('hls-videos.temp_disk')) // Output disk (can be S3, local, etc.)
            ->save("{$this->video->id}/{$this->quality->quality}/index.m3u8");

        $quality->update([
            'convert_data' => compact('width', 'height', 'videoKbps', 'bandwidth')
        ]);

        $quality->refresh();

        return new VideoConverted($quality);
    }


    protected function getActualVideoDimensions(): array
    {
        $ffprobe = \FFMpeg\FFProbe::create();
        $streams = $ffprobe
            ->streams(\Storage::disk(config('hls-videos.temp_disk'))->path($this->video->temp_video_path))
            ->videos();

        $videoStream = $streams->first();

        return [
            $videoStream->get('width'),
            $videoStream->get('height'),
        ];
    }

    protected function getQualitySettings($quality, int $actualWidth = null, int $actualHeight = null): array
    {
        // All available quality presets: [width, height, kbps]
        $presets = [
            '1080' => [1920, 1080, 3000],
            '720' => [1280, 720, 1000],
            '480' => [854, 480, 500],
            '360' => [640, 360, 400],
        ];

        $requested = $presets[$quality] ?? [1280, 720, 1000];

        // If we don't know the actual dimensions, just return as-is
        if ($actualWidth === null || $actualHeight === null) {
            return $requested;
        }

        [$reqWidth, $reqHeight, $reqKbps] = $requested;

        // If the video is already smaller than or equal to the requested size, find the best fitting preset
        if ($actualHeight <= $reqHeight) {
            // Walk presets from lowest to highest and pick the highest one that fits
            $sorted = collect($presets)->sortBy(fn ($p) => $p[1]); // sort by height asc

            $best = $sorted->first(); // fallback: lowest quality

            foreach ($sorted as $preset) {
                if ($preset[1] <= $actualHeight) {
                    $best = $preset;
                }
            }

            return $best;
        }

        // Video is larger than or equal to the requested quality — use it as-is
        return $requested;
    }
}
