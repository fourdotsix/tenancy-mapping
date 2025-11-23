<?php

namespace Fourdotsix\TenancyMapping\Contracts;

/**
 * @property string $value
 */
interface MappingType
{
    /**
     * Prefix used for saving mappings to DB
     */
    public function prefix(): string;

    /**
     * Should the generic maps be merged?
     *
     * This helps to have a fallback to generic maps
     * in case the mapping doesn't exists.
     */
    public function mergeGenerics(): bool;

    /**
     * Get base mapping directory
     */
    public function baseDirectory(): string;

    /**
     * Get mapping directory
     */
    public function mappingDirectory(): string;

    /**
     * Get mapping directory relative path
     */
    public function directory(): string;

    /**
     * Get generics mapping directory
     */
    public function directoryGeneric(): string;

    /**
     * Get tenants mapping Directory
     */
    public function directoryTenant(): string;

    /**
     * Get generic map file for Tenant Type
     */
    public function genericTypeMapFile(TenantType|string $tenantType): string;

    /**
     * Get generic map file
     */
    public function genericMapFile(): string;

    /**
     * Get tenant map file `/path/name`
     *
     * @param  string  $tenantId  Tenant ID
     */
    public function tenantMapFile(string $tenantId): string;

    /**
     * Valid File extensions for mappings
     *
     * @return string[]
     */
    public static function validExtensions(): array;
}
