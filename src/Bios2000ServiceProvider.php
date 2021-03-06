<?php

namespace Bios2000;

use Illuminate\Support\ServiceProvider;

class Bios2000ServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/bios2000.php' => config_path('bios2000.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/config/bios2000.php', 'bios2000'
        );
    }

    public function register()
    {
        $this->app->singleton('bios2000', function () {
            $Bios2000 = new Bios2000([
                'database' => config('database.connections.bios2000.database'),
                'driver' => config('database.connections.bios2000.driver'),
                'host' => config('database.connections.bios2000.host'),
                'port' => config('database.connections.bios2000.port'),
                'username' => config('database.connections.bios2000.username'),
                'password' => config('database.connections.bios2000.password'),
            ]);

            return $Bios2000;
        });
    }
}