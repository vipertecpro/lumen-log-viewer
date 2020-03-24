<?php

namespace LumenLogViewer\Providers;

use Illuminate\Support\ServiceProvider;

class LumenLogViewerServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../views', 'lumen-log-viewer');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void {
        $this->mergeConfigFrom(__DIR__ . '/../../config/logviewer.php', 'logviewer');
    }
}
