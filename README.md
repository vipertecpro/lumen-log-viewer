# Lumen 6 Log Viewer

## What is this?

Small log viewer for lumen. Looks like this:

![screenshot](https://github.com/vipertecpro/lumen-log-viewer/raw/master/screenshot.png)

## Install

Install via composer
```
composer require vipertecpro/lumen-log-viewer
```

Add Service Provider to `app/Providers/AppServiceProvider.php`
```php
$this->app->register(\LumenLogViewer\Providers\LumenLogViewerServiceProvider::class);
```

Add a route in your web routes file, like this:
```php 
/** @var Laravel\Lumen\Routing\Router $router */
$router->group(['namespace' => '\LumenLogViewer\Controllers'], function () use ($router) {
    $router->get('logs', 'LogViewerController@index');
});
```

Go to `http://yourapp/logs` or some other route

**Optionally** copy `vendor/vipertecpro/lumen-log-viewer/views/logviewer.blade.php` into `/resources/views/vendor/lumen-log-viewer/` for view customization:

