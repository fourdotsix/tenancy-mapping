<?php

namespace Fourdotsix\TenancyMapping\Database\Connectors;

use Fourdotsix\TenancyMapping\Database\Contracts\MappingDB;
use Illuminate\Redis\RedisManager;

class Redis extends RedisManager implements MappingDB
{
    /**
     * Pass methods onto the default Redis connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
