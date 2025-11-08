<?php

declare(strict_types=1);

namespace Fourdotsix\TenancyMapping\Bootstrappers;

use Fourdotsix\TenancyMapping\Database\Contracts\MappingDB;
use Illuminate\Contracts\Config\Repository;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class MappingTenancyBootstrapper implements TenancyBootstrapper
{
    /** @var array<string[]> Original prefixes of connections */
    public $originalPrefixes = [];

    public function __construct(protected Repository $config, protected MappingDB $mappingDB) {}

    public function bootstrap(Tenant $tenant): void
    {
        if ($this->config['mapper.database.connector'] === 'redis') {
            $this->bootstrapRedis($tenant);
        }
    }

    public function revert(): void
    {
        if ($this->config['mapper.database.connector'] === 'redis') {
            $this->revertRedis();
        }
    }

    protected function bootstrapRedis(Tenant $tenant)
    {
        foreach ($this->prefixedConnections() as $connection) {
            $prefix = str($this->config['tenancy.redis.prefix'])->replace('%tenant%', (string) $tenant->getTenantKey())->toString();
            $client = $this->mappingDB->connection($connection)->client();

            /** @var string $originalPrefix */
            $originalPrefix = $client->getOption(\Redis::OPT_PREFIX);

            $this->originalPrefixes['redis'][$connection] = $originalPrefix;
            $client->setOption(\Redis::OPT_PREFIX, $prefix);
        }
    }

    protected function revertRedis()
    {
        foreach ($this->prefixedConnections() as $connection) {
            $client = $this->mappingDB->connection($connection)->client();

            $client->setOption(\Redis::OPT_PREFIX, $this->originalPrefixes['redis'][$connection]);
        }

        unset($this->originalPrefixes['redis']);
    }

    /** @return string[] */
    protected function prefixedConnections(): array
    {
        return $this->config['tenancy.redis.prefixed_connections'];
    }
}
