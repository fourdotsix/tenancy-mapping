<?php

namespace Fourdotsix\TenancyMapping\Concerns;

use Fourdotsix\TenancyMapping\Contracts\TenantType;

trait FunctionalMappingTypes
{
    use EnumToArray;

    /**
     * Prefix used for saving mappings to DB
     */
    public function prefix(): string
    {
        return "map:{$this->value}:";
    }

    /**
     * Should the generic maps be merged?
     *
     * This helps to have a fallback to generic maps
     * in case the mapping doesn't exists.
     */
    public function mergeGenerics(): bool
    {
        return in_array($this, config('tenancy-mapping.mergeable', []));
    }

    /**
     * Get base mapping directory
     */
    public function baseDirectory(): string
    {
        return config('tenancy-mapping.directory');

    }

    /**
     * Get mapping directory
     */
    public function mappingDirectory(): string
    {
        return str($this->value)->plural();
    }

    /**
     * Get mapping directory relative path
     */
    public function directory(): string
    {
        return "{$this->baseDirectory()}/{$this->mappingDirectory()}";
    }

    /**
     * Get generics mapping directory
     */
    public function directoryGeneric(): string
    {
        return 'generics';
    }

    /**
     * Get tenants mapping Directory
     */
    public function directoryTenant(): string
    {
        return 'tenants';
    }

    /**
     * Get generic map file for Tenant Type
     */
    public function genericTypeMapFile(TenantType|string $tenantType): string
    {
        $type = is_string($tenantType) ? $tenantType : $tenantType->value;

        return "{$this->directory()}/{$this->directoryGeneric()}/{$type}";
    }

    /**
     * Get generic map file
     */
    public function genericMapFile(): string
    {
        return "{$this->directory()}/{$this->directoryGeneric()}/generic";
    }

    /**
     * Get tenant map file `/path/name`
     *
     * @param  string  $tenantId  Tenant UID
     */
    public function tenantMapFile(string $tenantId): string
    {
        return "{$this->directory()}/{$this->directoryTenant()}/{$tenantId}";
    }

    /**
     * Valid File extensions for mappings
     *
     * @return string[]
     */
    public static function validExtensions(): array
    {
        return [
            'yaml',
            'yml',
        ];
    }
}
