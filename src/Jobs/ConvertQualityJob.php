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

    /**
     * Wall-clock ceiling for a single conversion, in seconds.
     *
     * A transcode legitimately runs for minutes, so this has to be generous,
     * but it must not be infinite: a wedged ffmpeg process would otherwise
     * hold its worker for the lifetime of the queue. Set
     * `hls-videos.job_timeout` to 0 to opt back into no limit.
     *
     * Whichever queue connection carries this job must have a `retry_after`
     * larger than this value, or Redis will hand a still-running transcode to
     * a second worker and the two runs will overwrite each other's segments.
     */
    public $timeout;

    public function __construct(protected HlsVideoQuality $hlsVideoQuality)
    {
        $this->timeout = (int) config('hls-videos.job_timeout', 7200);
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
