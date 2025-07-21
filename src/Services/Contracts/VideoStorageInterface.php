<?php

namespace Tocaan\HlsVideos\Services\Contracts;

use Tocaan\HlsVideos\Models\HlsVideoQuality;

interface VideoStorageInterface
{
   public function uploadVideo(HlsVideoQuality $quality,$storageConfig): bool;
}
