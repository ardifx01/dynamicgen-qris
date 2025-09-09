<?php

namespace Kodinus\DynamicGenQris\Facades;

use Illuminate\Support\Facades\Facade;

class Qris extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'qris.generator';
    }
}
