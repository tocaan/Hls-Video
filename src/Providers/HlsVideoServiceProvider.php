<?php
namespace  HlsVideos\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HlsVideoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Config
        $this->publishes([
            __DIR__.'/../Config/hls-videos.php' => config_path('hls-videos.php'),
        ], 'config');

        // Views
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'hls-videos');

        // Routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/hls-videos.php', 'hls-videos'
        );
    }
}
