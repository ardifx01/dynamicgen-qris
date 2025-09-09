<?php

namespace Kodinus\DynamicGenQris;

use Illuminate\Support\ServiceProvider;

class QrisServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('qris.generator', function () {
            return new DynamicQRISGenerator();
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
