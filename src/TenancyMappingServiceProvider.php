<?php

namespace Fourdotsix\TenancyMapping;

use Fourdotsix\TenancyMapping\Commands\MappingClearCommand;
use Fourdotsix\TenancyMapping\Commands\MappingCompileCommand;
use Fourdotsix\TenancyMapping\Database\Connectors\FallGuy;
use Fourdotsix\TenancyMapping\Database\Connectors\Redis;
use Fourdotsix\TenancyMapping\Database\Contracts\MappingDB;
use Illuminate\Support\Arr;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TenancyMappingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('tenancy-mapping')
            ->hasConfigFile()
            ->hasMigrations('add_tenancy_mapping_columns')
            ->hasCommands(
                MappingClearCommand::class,
                MappingCompileCommand::class
            );

        // Publishes mappings directory
        $this->publishes([
            __DIR__.'/../mappings' => base_path($this->app['config']['tenancy-mapping']['directory'] ?? 'mappings'),
        ], 'tenancy-mapping-mappings');

        $this->registerMappingService();
    }

    protected function registerMappingService()
    {
        $this->registerMappingConnectors();
        $this->app->singleton('mapping', function ($app): Mapping {
            return new Mapping($app->make(MappingDB::class));
        });
    }

    protected function registerMappingConnectors()
    {
        // Register Mapping database connectors
        $this->app->bind(FallGuy::class, fn ($app) => new FallGuy);
        $this->app->bind(Redis::class, function ($app) {
            $config = $app->make('config')->get('database.redis', []);

            return new Redis($app, Arr::pull($config, 'client', 'phpredis'), $config);
        });

        // Make it possible to instantiate using MappingDB contract
        $this->app->singleton(MappingDB::class, function ($app) {
            /** @var \Illuminate\Contracts\Foundation\Application $app */
            $config = $app->make('config');
            $connector = $config->get('tenancy-mapping.database.connector', '');
            $fallback = $config->get('tenancy-mapping.database.fallback.class', FallGuy::class);
            $class = $config->get("tenancy-mapping.database.connectors.{$connector}.class", $fallback);

            return $app->make($class);
        });
    }
}
