<?php

namespace  HlsVideos\Models;

use Illuminate\Database\Eloquent\Model;
use HlsVideos\Jobs\ConvertQualityJob;
use HlsVideos\Services\VideoService;
use Illuminate\Support\Facades\Storage;

class HlsVideoQuality extends Model
{
    const CONVERTING = 'converting';
    const UPLOADING = 'uploading';
    const READY = 'ready';

    protected $guarded = ['id'];
    protected $casts = [
        'convert_data' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($videoQuality) {
            
            // Create directories
            if (!is_dir($videoQuality->process_folder_path)) {
                mkdir($videoQuality->process_folder_path, 0755, true);
            }

            ConvertQualityJob::dispatch($videoQuality)->onQueue('default');;
        });

        static::saving(function ($videoQuality) {
            if (! $videoQuality->isDirty('status')) {
                return;
            }

            if ($videoQuality->status === self::CONVERTING) {
                $videoQuality->playlist_content = null;
                $videoQuality->ts_files_count = null;

                return;
            }

            if ($videoQuality->status === self::READY && ! $videoQuality->isOriginalQuality()) {
                $videoQuality->cachePlaylistContent();
            }
        });
    }

    public function video(){

        return $this->belongsTo(HlsVideo::class,'hls_video_id');
    }

    public function getProcessFolderPathAttribute(){

        return Storage::disk(config('hls-videos.temp_disk'))->path("{$this->hls_video_id}/{$this->quality}");
    }

    public function getFolderPathAttribute(){

        return "{$this->hls_video_id}/{$this->quality}";
    }

    public function scopeNotReady($q){

        return $q->where('status', '!=', self::READY);
    }

    public function updateStatusTo($status){

        return $this->update(['status' => $status]);
    }

    public function isOriginalQuality(): bool
    {
        return isset($this->convert_data['original']) && $this->convert_data['original'] === true;
    }

    public function cachePlaylistContent(): void
    {
        if ($this->isOriginalQuality()) {
            return;
        }

        $playlistPath = "{$this->folder_path}/vd.m3u8";
        $tempDisk = Storage::disk(config('hls-videos.temp_disk'));
        $streamDisk = Storage::disk(config('hls-videos.stream_disk'));

        if ($tempDisk->exists($playlistPath)) {
            $content = $tempDisk->get($playlistPath);
            $sourceDisk = $tempDisk;
        } elseif ($streamDisk->exists($playlistPath)) {
            $content = $streamDisk->get($playlistPath);
            $sourceDisk = $streamDisk;
        } else {
            throw new \RuntimeException("Playlist not found: {$playlistPath}");
        }

        $this->playlist_content = $content;
        $this->ts_files_count = $this->countTsFiles($sourceDisk, $content);
    }

    public function ensurePlaylistCache(): void
    {
        if ($this->isOriginalQuality() || $this->playlist_content !== null) {
            return;
        }

        $this->cachePlaylistContent();
        $this->save();
    }

    protected function countTsFiles($disk, string $content): int
    {
        $tsFilesCount = collect($disk->files($this->folder_path))
            ->filter(fn ($file) => str_ends_with($file, '.ts'))
            ->count();

        if ($tsFilesCount > 0) {
            return $tsFilesCount;
        }

        return count(VideoService::getTsFilesFromPlaylistFile($content));
    }
}
