<?php

namespace Fourdotsix\TenancyMapping\Database\Connectors;

use Fourdotsix\TenancyMapping\Database\Contracts\MappingDB;

/**
 * This is a fallback connector that does nothing and just implements
 * all the required methods needed to act as a fall guy :P
 */
class FallGuy implements MappingDB
{
    public function __call($method, $args) {}

    public static function __callStatic($method, $args) {}
}
