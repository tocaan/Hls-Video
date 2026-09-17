<?php
namespace  HlsVideos\Providers;

use HlsVideos\Components\{VideoManeger, HlsPlayer, HlsPlayerScript};
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HlsVideoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/hls-videos.php' => config_path('hls-videos.php'),
        ], 'config');

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'hls-videos');

        // Routes
        //
        // These are the bundled uploader's endpoints. Apps that drive uploads
        // through their own admin panel should set `register_routes` to false
        // so the `hls/videos` prefix never exists at all.
        if (config('hls-videos.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        
        // Register blade component
        Blade::component('hls-video-manager', VideoManeger::class);
        Blade::component('hls-play', HlsPlayer::class);
        Blade::component('hls-play-js', HlsPlayerScript::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                \HlsVideos\Console\Commands\CachePlaylistsCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/hls-videos.php', 'hls-videos'
        );
    }
}
