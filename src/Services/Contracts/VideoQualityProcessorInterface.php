<?php

namespace Tocaan\HlsVideos\Services\Contracts;

use Tocaan\HlsVideos\DTOS\VideoConverted;
use Tocaan\HlsVideos\Models\HlsVideoQuality;

interface VideoQualityProcessorInterface
{
   public function convertVideo($videoFile, HlsVideoQuality $quality): VideoConverted;
}
