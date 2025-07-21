<?php

namespace  HlsVideos\DTOS;

use  HlsVideos\Models\HlsVideo;
use  HlsVideos\Models\HlsVideoQuality;
use Illuminate\Support\Facades\Storage;

class VideoConverted
{
   public $video;

   public function __construct(public HlsVideoQuality $videoQuality) {

      $this->video = $this->videoQuality->video;

      $this->handlingTheQualityPlaylist();
      $this->createOrUpdateMasterPlaylist();

      $this->videoQuality->updateStatusTo(HlsVideoQuality::UPLOADING);
      $this->uploadVideoToStorage();

      $this->videoQuality->updateStatusTo(HlsVideoQuality::READY);
      $this->updateVideoUploaded();
   }

   private function uploadVideoToStorage()
   {
      foreach(config('hls-videos.storages') as $key => $storage){
         $service = new $storage['service'];
         $service->uploadVideo($this->videoQuality, $storage);
      }
   }

   private function updateVideoUploaded()
   {
      $this->video->update(['status' => HlsVideo::READY]);

      if(!$this->video->qualities()->notReady()->count()){

        Storage::disk(config('hls-videos.temp_disk'))->deleteDirectory($this->video->id);
      }
   }

   private function handlingTheQualityPlaylist()
   {
      try {
         $playlistIndexFile = "{$this->videoQuality->process_folder_path}/vd.m3u8";
         // Read the playlist file
         $content = file_get_contents($playlistIndexFile);
         if ($content === false) {
            throw new \Exception("Could not read playlist file");
         }

         // Replace .ts file references with the custom route
         // This regex matches lines ending with .ts (optionally preceded by whitespace)
         $newContent = preg_replace_callback(
            '/^([^\r\n]*?)([a-zA-Z0-9_\-]+\.ts)$/m',
            function ($matches) {
                  $fileName = $matches[2];
                  // If you have access to the route() helper, use it. Otherwise, build the URL manually:
                  $url = route(config('hls-videos.access_route_stream'), [
                  $this->videoQuality->hls_video_id, 
                  $this->videoQuality->quality, 
                  $fileName
               ]);
                  return $matches[1] . $url;
            },
            $content
         );

         // Write the modified content back to the file (overwrite)
         file_put_contents($playlistIndexFile, $newContent);

       } catch (\Exception $e) {
           // Handle error as needed
       }
   }


   /**
    * Create or update a master playlist that supports multiple qualities.
    *
    * @param array $qualities Array of qualities, each as ['quality' => ..., 'indexFileName' => ..., 'bandwidth' => ..., 'resolution' => ...]
    * @param int|string $videoId
    * @param string $basePath
    */
   private function createOrUpdateMasterPlaylist()
   {
       try {
           $masterPlaylist = "#EXTM3U\n";
           $masterPlaylist .= "#EXT-X-VERSION:3\n";

           foreach ($this->video->qualities as $quality) {
               // Set defaults if not provided
               $bandwidth = isset($quality->bandwidth) ? $quality->bandwidth : 1000000;
               $resolution = isset($quality->resolution) ? $quality->resolution : '1280x720';
               $q = $quality->quality;

               $pathToFile = route(config('hls-videos.access_route_stream'), [$this->video->id, $q, 'vd.m3u8']);
               $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$resolution}\n";
               $masterPlaylist .= "$pathToFile\n";
           }

           $masterPath = $this->video->temp_video_folder . '/index.m3u8';
           file_put_contents($masterPath, $masterPlaylist);

       } catch (\Exception $e) {
           // Handle error as needed
       }
   }
}
