<?php
namespace HlsVideos\Jobs;

use HlsVideos\DTOS\VideoConverted;
use HlsVideos\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use HlsVideos\Factories\VideoQualityProcessorFactory;
use HlsVideos\Models\HlsVideo;
use HlsVideos\Models\HlsVideoQuality;


class ConvertQualityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0; // infinite timeout

    public function __construct(protected HlsVideoQuality $hlsVideoQuality)
    {
    }

    public function handle()
    {
        try {
            $quality = $this->hlsVideoQuality->quality;
            $video = $this->hlsVideoQuality->video;
            $videoService = new VideoService();

            if ($video->status != HlsVideo::READY)
                $video->update(['status' => HlsVideo::PROCESSING]);

            $service = VideoQualityProcessorFactory::make($quality);

            switch ($this->hlsVideoQuality->status) {
                case HlsVideoQuality::UPLOADING:
                    new VideoConverted($this->hlsVideoQuality);
                    break;
                case HlsVideoQuality::READY:
                    break;
                default:
                    $this->hlsVideoQuality->updateStatusTo(HlsVideoQuality::CONVERTING);
                    $videoService->startingConvertQuality($this->hlsVideoQuality);
                    $service->convertVideo($video->temp_video, $this->hlsVideoQuality);
                    break;
            }
        } catch (\Throwable $e) {
            \Log::error("FAILED ConvertQualityJob: {$e->getMessage()}");
            throw $e;
        }
    }
}
