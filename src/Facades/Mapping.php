<?php

declare(strict_types=1);

namespace Fourdotsix\TenancyMapping\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Fourdotsix\TenancyMapping\Mapping
 */
class Mapping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Fourdotsix\TenancyMapping\Mapping::class;
    }
}
