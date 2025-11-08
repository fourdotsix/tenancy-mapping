<?php

use Fourdotsix\TenancyMapping\Database\Connectors\FallGuy;
use Fourdotsix\TenancyMapping\Database\Connectors\Redis;
use Fourdotsix\TenancyMapping\Enums\MappingType;

return [
    /*
    | ---------------------------------------------------------------------------
    | Mappings Directory
    | ---------------------------------------------------------------------------
    | Directory where mappings are stored. This is passed to the Laravel's
    | `base_path()` helper.
    |
    | Default: `mappings`
    */
    'directory' => './mappings',

    /*
    | ---------------------------------------------------------------------------
    | Mappings Type Enum
    | ---------------------------------------------------------------------------
    | Enum used for Mappings Type
    */
    // 'type' => MappingType::class,

    /*
    | ---------------------------------------------------------------------------
    | Mergeable Mappings Types
    | ---------------------------------------------------------------------------
    | These are the mapping types that support merging, i.e. when a tenant mapping
    | file is present, it would first load the generic as base and then merge
    | the tenant mappings. This gives an option to have custom mappings per tenants
    | with only overrides, while still having generic defaults.
    */
    'mergeable' => [
        MappingType::Settings,
    ],

    /*
    | ---------------------------------------------------------------------------
    | Mappings Database Config
    | ---------------------------------------------------------------------------
    | - Columns: Database columns used by migration
    | - Connector: Database Connector used for storing compiled mappings
    | - Connectors: Database connectors for storing compiled mappings
    | - Fallback: Fallback connectors
    | Default: `redis`
    */
    'database' => [
        'columns' => [
            'tenant_type' => 'type',
        ],

        'connector' => env('MAPPING_DB_CONNECTOR', 'redis'),

        'fallback' => [
            'class' => FallGuy::class,
        ],

        'connectors' => [
            'redis' => [
                'class' => Redis::class,
            ],
        ],
    ],

];
