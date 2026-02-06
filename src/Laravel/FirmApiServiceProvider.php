<?php

declare(strict_types=1);

namespace FirmApi\Laravel;

use FirmApi\Client;
use Illuminate\Support\ServiceProvider;

class FirmApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/firmapi-sdk.php', 'firmapi-sdk');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['firmapi-sdk'];

            return new Client(
                apiKey: $config['api_key'] ?? '',
                baseUrl: $config['base_url'] ?? null,
                timeout: $config['timeout'] ?? 30,
            );
        });

        $this->app->alias(Client::class, 'firmapi');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/firmapi-sdk.php' => config_path('firmapi-sdk.php'),
            ], 'firmapi-config');
        }
    }
}
