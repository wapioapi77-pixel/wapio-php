<?php

declare(strict_types=1);

namespace Wapio\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Wapio\Http\RetryConfig;
use Wapio\Wapio;

/**
 * Auto-discovered via `extra.laravel.providers` in composer.json.
 *
 * - Publishes config to `config/wapio.php` with `vendor:publish --tag=wapio-config`.
 * - Registers `Wapio\Wapio` as a singleton, resolved from `config('wapio')`.
 * - Aliases the `Wapio` facade for static-style calls (`Wapio::sendText([...])`).
 */
class WapioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/wapio.php', 'wapio');

        $this->app->singleton(Wapio::class, function (Container $app): Wapio {
            $config = (array) $app['config']->get('wapio', []);
            $retryConfig = $config['retry'] ?? [];

            return Wapio::create([
                'api_key' => $config['api_key'] ?? null,
                'personal_access_token' => $config['personal_access_token'] ?? null,
                'base_url' => $config['base_url'] ?? null,
                'timeout' => (float) ($config['timeout'] ?? 60.0),
                'retry' => new RetryConfig(
                    enabled: (bool) ($retryConfig['enabled'] ?? true),
                    maxRetries: (int) ($retryConfig['max_retries'] ?? 3),
                    initialBackoff: (float) ($retryConfig['initial_backoff'] ?? 0.5),
                    maxBackoff: (float) ($retryConfig['max_backoff'] ?? 10.0),
                ),
            ]);
        });

        $this->app->alias(Wapio::class, 'wapio');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__ . '/config/wapio.php' => config_path('wapio.php')],
                'wapio-config',
            );
        }

    }

    /**
     * @return array<int,string>
     */
    public function provides(): array
    {
        return [Wapio::class, 'wapio'];
    }
}
