<?php

namespace HlsVideos\Services\Qualities;

use HlsVideos\DTOS\VideoConverted;
use HlsVideos\Models\HlsVideoQuality;
use HlsVideos\Services\Contracts\VideoQualityProcessorInterface;
use FFMpeg;
use FFMpeg\Format\Video\X264;

class OriginalVideoService implements VideoQualityProcessorInterface
{
    protected $quality;
    protected $video;
    protected $headers;


    public function convertVideo($videoFile, HlsVideoQuality $quality): VideoConverted
    {

        $this->video = $quality->video;
        $this->quality = $quality;

        // By default, empty the target quality folder (remove all files but keep the folder)
        $qualityFolder = "{$this->video->id}/{$this->quality->quality}";
        $disk = \Storage::disk(config('hls-videos.temp_disk'));
        if ($disk->exists($qualityFolder)) {
            $files = $disk->files($qualityFolder);
            foreach ($files as $file) {
                $disk->delete($file);
            }
        }

        // Ensure the quality folder exists in the temp disk, then copy the video file into it
        $qualityFolderAbsolute = $disk->path($qualityFolder);
        if (!is_dir($qualityFolderAbsolute)) {
            mkdir($qualityFolderAbsolute, 0755, true);
        }
        // Copy the original video into the quality folder
        // Get source and destination paths
        $sourcePath = $disk->path($this->video->temp_video_path);
        $destinationPath = $qualityFolderAbsolute . '/' . basename($this->video->temp_video_path);
        copy($sourcePath, $destinationPath);
        
        $quality->update([
            'convert_data' => ['original' => true]
        ]);

        $quality->refresh();

        return new VideoConverted($quality);
    }
}
