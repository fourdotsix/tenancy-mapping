<?php

use Fourdotsix\TenancyMapping\Enums\MappingType;
use Fourdotsix\TenancyMapping\Facades\Mapping;
use Illuminate\Support\Stringable;

if (! function_exists('mapping')) {
    /**
     * Get mappings for specific key
     *
     * @param  mixed  $key
     */
    function mapping(MappingType $type, $key): mixed
    {
        $mapping = Mapping::get($type, $key);

        return filled($mapping) ? $mapping : null;
    }
}

if (! function_exists('descriptor')) {
    /**
     * Get descriptor's mappings for a key
     *
     * @param  string  $key  Key to fetch the value
     * @param  string  $default  Default value to return when key is not found or null
     * @param  bool  $i18n  Whether to translate the output
     */
    function descriptor(string $key, ?string $default = null, bool $i18n = true): Stringable
    {
        $mapping = mapping(MappingType::Descriptor, $key) ?? $default;

        return str($i18n ?
            __($mapping) :
            $mapping);
    }
}

if (! function_exists('settings_map')) {
    /**
     * Get settings's mappings for a key
     *
     * @param  string  $key  Key to fetch the value
     * @param  mixed  $default  Default value to return when key is not found or null
     * @param  mixed  $return_type  specify an expected php return type ex: `int`, `bool`, `string`, `float`; default is `null` i.e. return would be as is.
     */
    function settings_map(string $key, $default = null, $return_type = null): mixed
    {
        $mapping = mapping(MappingType::Settings, $key) ?? $default;

        if (! is_null($return_type)) {

            if (is_array($mapping) && $return_type != 'array') {
                $mapping = json_encode($mapping, flags: JSON_THROW_ON_ERROR);
            }

            settype($mapping, $return_type);
        }

        return $mapping;
    }
}
