<?php

namespace Fourdotsix\TenancyMapping;

use Dallgoot\Yaml\Types\YamlObject;
use Dallgoot\Yaml\Yaml;
use Fourdotsix\TenancyMapping\Contracts\MappingType;
use Fourdotsix\TenancyMapping\Database\Contracts\MappingDB;
use Fourdotsix\TenancyMapping\Exceptions\MappingFileNotFound;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Stancl\Tenancy\Contracts\Tenant;

final class Mapping
{
    /**
     * Create mapping Service
     */
    public function __construct(protected MappingDB $dbInstance) {}

    /**
     * Get mapping values for a key
     */
    public function get(MappingType $type, string $key): mixed
    {
        return $this->unserialize($this->dbInstance->get("{$type->prefix()}$key"));
    }

    /**
     * Get all mapping values for a Mapping Type
     */
    public function all(MappingType $type): array
    {
        $keys = $this->dbInstance->keys("*{$type->prefix()}*");
        $tenantPrefix = $this->getMappingPrefix();

        $stripped = array_map(
            fn ($value) => str_replace($tenantPrefix, '', $value),
            is_array($keys) ? $keys : []
        );

        $array = [];

        if (! empty($stripped)) {
            $mget = $this->dbInstance->mget($stripped);

            array_map(
                function ($key, $value) use (&$array, $type) {
                    $array[str_replace($type->prefix(), '', $key)] = $this->unserialize($value);
                },
                $stripped,
                is_array($mget) ? $mget : []
            );
        }
        ksort($array);

        return $array;
    }

    /**
     * Unserialize the data
     *
     * @param  mixed  $data
     */
    protected function unserialize($data)
    {
        // Un-serialize if needed
        if (json_validate($data)) {
            $data = json_decode($data, true);
        }

        return $data;
    }

    /**
     * Serialize the data
     *
     * @param  mixed  $data
     */
    protected function serialize($data)
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data, flags: JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    /**
     * Compile the mappings
     */
    public function compile(Tenant $tenant, MappingType $type): YamlObject
    {
        $method = str("map {$type->value}")->camel()->toString();

        if (method_exists($this, $method)) {
            // If specific mapping method exists, ex: `mapDescriptor()`
            return $this->{$method}($tenant, $type);
        }

        return $this->mergeAndMap($tenant, $type);

    }

    /**
     * Clear the compiled mappings
     */
    public function clear(MappingType $type): bool
    {
        $keys = $this->dbInstance->keys("*{$type->prefix()}*");
        $tenantPrefix = $this->getMappingPrefix();

        // Since tenancy bootstrappers will prefix the keys, it would result in double prefix if not stripped
        $stripped = array_map(fn ($value) => str_replace($tenantPrefix, '', $value), $keys);

        return $this->dbInstance->del($stripped);
    }

    /**
     * Merge mappings if the MappingType supports merging
     */
    protected function mergeAndMap(Tenant $tenant, MappingType $type): YamlObject
    {
        $tenantFile = $this->getTenantMappingFile($tenant, $type);

        $map = fn (string $file) => $this->map($tenant, $type, $file);

        if ($type->mergeGenerics()) {
            // try to use generic mapping for tenant type
            $genericTenant = $this->getMappingFile($type->genericTypeMapFile($tenant->type), $type);
            if (is_string($genericTenant['file'])) {
                $map($genericTenant['file']);
            }
            // try to use generic mappings
            $generic = $this->getMappingFile($type->genericMapFile(), $type);
            if (is_string($generic['file'])) {
                $map($generic['file']);
            }
        }

        // Re-compile with tenant mapping file
        return $map($tenantFile);

    }

    /**
     * Map any Mapping types and save to database
     */
    protected function map(Tenant $tenant, MappingType $type, string $file): YamlObject
    {
        /** @var object{mappings: object|array} $map */
        $map = Yaml::parseFile($file);
        $collect = [];

        foreach ($map->mappings as $key => $value) {
            $collect = array_merge($collect, $this->processKeyValue($type, $key, $value));
        }

        foreach ($collect as $key => $value) {
            $this->addMapToDatabase($type, $key, $value);
        }

        return $map;
    }

    /**
     * Convert mappings to Key & Value pairs.
     * It converts objects & array to dot notation strings for keys
     *
     * @param  mixed  $key
     * @param  mixed  $value
     */
    protected function processKeyValue(MappingType $type, $key, $value): array
    {
        $pr_key = $key;
        $pr_val = $value;
        $processed = [];

        if (is_array($value)) {
            foreach ($value as $arr_key => $val) {
                if (Arr::isAssoc($value)) {
                    $processed = array_merge($processed, $this->processKeyValue($type, "{$pr_key}.{$arr_key}", $val));
                } else {
                    if (! is_array($val) && ! is_object($val)) {
                        // This is an non-associative array with primitive values, so we keep it as an array
                        $processed[$pr_key][] = Arr::first($this->processKeyValue($type, $pr_key, $val));
                    } else {
                        $processed = array_merge($processed, $this->processKeyValue($type, "{$pr_key}", $val));
                    }
                }
            }
        } elseif (is_object($value)) {
            // convert nested obj to array
            $toArray = json_decode(json_encode($value, flags: JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            $processed = $this->processKeyValue($type, $key, $toArray);
        } else {
            $processed = [$pr_key => $pr_val];
        }

        return $processed;

    }

    /**
     * Add the mappings (key/value) to the database
     *
     * @param  mixed  $value
     */
    protected function addMapToDatabase(MappingType $type, string $key, $value)
    {
        return $this->dbInstance->set("{$type->prefix()}$key", $this->serialize($value));
    }

    /**
     * Get the tenant's mapping file
     *
     * @throws \Fourdotsix\TenancyMapping\Exceptions\MappingFileNotFound
     */
    protected function getTenantMappingFile(Tenant $tenant, MappingType $type): string
    {
        $mapping = $this->getMappingFile($type->tenantMapFile($tenant->id), $type);
        $searched = $mapping['searched'];
        $tenantTypeColumn = config('tenancy-mapping.database.columns.tenant_type');

        if (! is_string($mapping['file'])) {
            // If no tenant mapping exists, try to use generic mapping for tenant type
            $mapping = $this->getMappingFile($type->genericTypeMapFile($tenant->{$tenantTypeColumn}), $type);
            $searched = array_merge($searched, $mapping['searched']);
            if (! is_string($mapping['file'])) {
                // If no 'tenant type generic' mapping exists, use generic mappings
                $mapping = $this->getMappingFile($type->genericMapFile(), $type);
                $searched = array_merge($searched, $mapping['searched']);
            }
        }

        // if no mappings found, throw exception
        if (! is_string($mapping['file'])) {
            $searchedFiles = implode("\n- ", $searched);
            throw new MappingFileNotFound("Mapping files not found! Files searched:\n- {$searchedFiles}");
        }

        return $mapping['file'];
    }

    protected function getMappingFile(string $filepath, MappingType $type)
    {
        $filesystem = new FilesystemManager(app())->createLocalDriver(['root' => './'], 'root');
        $file = null;
        $searched = [];

        foreach ($type::validExtensions() as $ext) {
            $file = "{$filepath}.{$ext}";
            $searched[] = $file;

            if ($filesystem->exists($file)) {
                break;
            }

            $file = null;
        }

        return ['file' => $file, 'searched' => $searched];
    }

    /**
     * Get the Mapping Prefix
     *
     * @return string
     */
    protected function getMappingPrefix()
    {
        $tenant = tenant();

        return str_replace('%tenant%', $tenant->id ?? '', config('tenancy.redis.prefix'));
    }
}
