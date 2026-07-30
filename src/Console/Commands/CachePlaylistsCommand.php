<?php

namespace HlsVideos\Console\Commands;

use HlsVideos\Models\HlsVideoQuality;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CachePlaylistsCommand extends Command
{
    protected $signature = 'hls-videos:cache-playlists
                            {--video= : Only process a single video id}
                            {--force : Re-read and overwrite qualities that already have a cached playlist}
                            {--chunk=100 : Rows loaded per chunk}';

    protected $description = 'Backfill cached playlist content for existing HLS video qualities';

    public function handle(): int
    {
        $query = HlsVideoQuality::query()
            ->where('status', HlsVideoQuality::READY);

        if ($videoId = $this->option('video')) {
            $query->where('hls_video_id', $videoId);
        }

        if (! $this->option('force')) {
            $query->whereNull('playlist_content');
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No qualities to process.');

            return self::SUCCESS;
        }

        $cached = 0;
        $skipped = 0;
        $failed = 0;
        $chunkSize = (int) $this->option('chunk');

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $query->chunkById($chunkSize, function ($qualities) use (&$cached, &$skipped, &$failed, $progressBar) {
            foreach ($qualities as $quality) {
                try {
                    if ($quality->isOriginalQuality()) {
                        $skipped++;
                        $progressBar->advance();

                        continue;
                    }

                    $quality->cachePlaylistContent();
                    $quality->saveQuietly();
                    $cached++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('Failed to cache playlist for quality', [
                        'quality_id' => $quality->id,
                        'hls_video_id' => $quality->hls_video_id,
                        'quality' => $quality->quality,
                        'message' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);
        $this->info("Done. Cached: {$cached}, Skipped: {$skipped}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
