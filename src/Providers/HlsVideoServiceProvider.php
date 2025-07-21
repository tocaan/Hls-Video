<?php
namespace Modules\Course\HlsVideo;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HlsVideoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::directive('hlsVideoStyles', function () {
            return "<?php echo \\Modules\\Course\\HlsVideo\\Src\\Services\\AssetManagerService::outputStyles(); ?>";
        });

        Blade::directive('hlsVideoScripts', function () {
            return "<?php echo \\Modules\\Course\\HlsVideo\\Src\\Services\\AssetManagerService::outputScripts(); ?>";
        });

        // If you publish assets
        // $this->publishes([...]);
    }

    public function register(): void
    {
        // if you want to merge config or bind things
    }
}
