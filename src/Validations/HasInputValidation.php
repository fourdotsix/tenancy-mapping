<?php

namespace Fourdotsix\TenancyMapping\Validations;

use Fourdotsix\TenancyMapping\Enums\MappingType;

trait HasInputValidation
{
    /**
     * Validate the mapping types
     */
    protected function typeValidation(array $input): array
    {
        $validTypes = array_merge(['all'], MappingType::values());
        foreach ($input as $value) {
            if (! in_array($value, $validTypes)) {
                $this->fail('The types should be either `all` or one of '.MappingType::class);
            }
        }

        if (in_array('all', $input)) {
            // Overwrite $types to have all the available types
            $input = MappingType::values();
        }

        return $input;
    }

    /**
     * Validate the tenants
     */
    protected function tenantValidation(array $tenants = []): array
    {
        foreach ($tenants as $tenant) {
            $model = config('tenancy.models.tenant');
            $find = $model::find($tenant);
            if (! $find) {
                $this->fail("Tenant [$tenant] not found!");
            }
        }

        return $tenants;
    }
}
