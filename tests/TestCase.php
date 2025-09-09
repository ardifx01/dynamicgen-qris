<?php

namespace Kodinus\DynamicGenQris\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Kodinus\DynamicGenQris\QrisServiceProvider;
use Kodinus\DynamicGenQris\Facades\Qris;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [QrisServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Qris' => Qris::class];
    }
}
