<?php

namespace Fourdotsix\TenancyMapping\Enums;

use Fourdotsix\TenancyMapping\Concerns\FunctionalMappingTypes;
use Fourdotsix\TenancyMapping\Contracts\MappingType as Contract;

/**
 * @mixin \Fourdotsix\TenancyMapping\Enums\MappingType
 */
enum MappingType: string implements Contract
{
    use FunctionalMappingTypes;

    case Descriptor = 'descriptor';
    case Settings = 'settings';

}
