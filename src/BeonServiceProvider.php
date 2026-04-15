<?php

namespace Beon\Laravel;

use Beon\Laravel\Http\BeonWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BeonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/beon.php', 'beon'
        );

        // Register BeonClient as singleton
        $this->app->singleton(BeonClient::class, function ($app) {
            $config = $app['config']['beon'];

            return new BeonClient(
                $config['base_url'],
                $config['api_key'],
                (int) $config['timeout'],
            );
        });

        // Register BeonManager as singleton bound to 'beon'
        $this->app->singleton('beon', function ($app) {
            return new BeonManager(
                $app->make(BeonClient::class)
            );
        });

        // Alias for Facade
        $this->app->alias('beon', BeonManager::class);
    }

    public function boot(): void
    {
        // Publish config: php artisan vendor:publish --tag=beon-config
        $this->publishes([
            __DIR__ . '/../config/beon.php' => config_path('beon.php'),
        ], 'beon-config');

        // Register Route macro: Route::beonWebhook('/beon/webhook')
        Route::macro('beonWebhook', function (string $path = '/beon/webhook') {
            Route::get($path,  [BeonWebhookController::class, 'verify'])->name('beon.webhook.verify');
            Route::post($path, [BeonWebhookController::class, 'handle'])->name('beon.webhook.handle');
        });
    }
}
