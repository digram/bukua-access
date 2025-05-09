<?php

namespace BukuaAccess\Providers;

use Illuminate\Support\ServiceProvider;
use BukuaAccess\Controllers\BukuaAccessController;

class BukuaAccessServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bukua-access.php', 'services');
    }

    public function register()
    {
        // register BukuaAccess facade
        $this->app->singleton('bukuaaccess', function ($app) {
            return new BukuaAccessController();
        });
    }
}
