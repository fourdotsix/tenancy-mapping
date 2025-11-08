<p align="center"><a href="https://fourdotsix.com" target="_blank"><img src="https://cdn.fourdotsix.com/images/4.6/logo/4.6-bimi.svg" width="200" alt="fourdotsix.com Logo"></a><br><strong>four●six</strong> // tenancy-mapping</p>

# Tenant data handling (mapping)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fourdotsix/tenancy-mapping.svg?style=flat-square)](https://packagist.org/packages/fourdotsix/tenancy-mapping)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/fourdotsix/tenancy-mapping/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/fourdotsix/tenancy-mapping/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/fourdotsix/tenancy-mapping/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/fourdotsix/tenancy-mapping/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/fourdotsix/tenancy-mapping.svg?style=flat-square)](https://packagist.org/packages/fourdotsix/tenancy-mapping)

Tenant specific data handling for [stancl/tenancy](https://github.com/archtechx/tenancy).

## What?

Mappings are the key-value pairs that can be used to get non-sensitive data while maintaining the tenant context. These are compiled and stored in an in-memory database, ex: Redis, giving faster data access. On a base level, there are two types of mappings:

- Descriptors: These are useful when different tenant types refers to some part of the application differently.

    For example: An LMS SaaS application, can serve both businesses and educational institutions. An educational institution might refer to it's learners as Students while businesses might refer them as Employees. In such cases descriptor comes handy for dynamic descriptors. The descriptor mappings would be as such:

    ```yaml
    ---
    # file: university.yml
    name: Institution ORG mappings
    description: Mappings for tenant university.example.co
    mappings:
        learner: student
        institute: university
    ```

    This can be accessed as such:

    ```php
        descriptor('learner')->title(); // Student
        descriptor(key: 'institute', default: 'institute'); // university
        descriptor(key: 'institute', default: 'institute', i18n: true); // महाविद्यालय
    ```

- Settings: As the name suggests, these can be used for non-sensitive tenant specific settings. It provides faster access to settings data using key-value pairs and keeping these in YAML files can provide an audit trail for changes in GIT. Settings are merged by default, i.e., there can be a separate tenant's YAML file created with overrides and compilation will merge the settings in generic settings YAML file during compilation. This can be disabled in config file, if needed.

## Why?

When creating a SaaS application, the biggest challenge is customization according to the tenant. Not all organizations are the same, therefore, tailoring specific software features can become impossible while onboarding new tenants and scalability can really take a hit.

## Installation

1. Install the package via composer:

    ```bash
    composer require fourdotsix/tenancy-mapping
    ```

2. Publish the config file with:

    ```bash
    php artisan vendor:publish --tag="tenancy-mapping-config"
    ```

3. Publish the migrations with:

    ```bash
    php artisan vendor:publish --tag="tenancy-mapping-migrations"
    ```

4. Publish the mappings folder with:

    ```bash
    php artisan vendor:publish --tag="tenancy-mapping-mappings"
    ```

5. Add the Tenancy Bootstrapper in your `config/tenancy.php`:

    ```php
    'bootstrappers' => [
        ...
        // Integration bootstrappers
        ...
        Fourdotsix\TenancyMapping\Bootstrappers\MappingTenancyBootstrapper::class,
    ],
    ```

6. (optional|**recommended)** Set `TenantType` enum: This helps in creating a consistent and safe way of defining tenant types. Although it's optional, it's still recommended.

    - Create an Enum and implement `Fourdotsix\TenancyMapping\Contracts\TenantType`, Ex:

        ```php
        <?php

        namespace App\Enums;

        use Fourdotsix\TenancyMapping\Concerns\EnumToArray;
        use Fourdotsix\TenancyMapping\Contracts\TenantType as Contract;

        enum TenantType: string implements Contract
        {
            use EnumToArray;

            case Institute = 'institute';
            case University = 'university';
            case Business = 'business';
        }

        ```

    - Add casts in your Tenant Model:

        ```php
        protected $casts = [
            ...
            'type' => TenantType::class,
            ...
        ];
        ```

7. (optional) Overriding `MappingType`: Although this might not be needed in normal scenarios, but by overriding `MappingType` Enum you can add new mapping types as well as override Enum methods. After creating a new `MappingType` Enum you can directly use that in functions & Facades while fetching mappings.

    - Create an Enum that implements `Fourdotsix\TenancyMapping\Contracts\MappingType` and uses trait `Fourdotsix\TenancyMapping\Concerns\FunctionalMappingTypes`, Ex:

        ```php
        <?php

        namespace App\Enums;

        use Fourdotsix\TenancyMapping\Concerns\FunctionalMappingTypes;
        use Fourdotsix\TenancyMapping\Contracts\MappingType as Contract;

        enum MappingType: string implements Contract
        {
            use FunctionalMappingTypes;

            case Descriptor = 'descriptor';
            case Settings = 'settings';

        }
        ```

## Usage

Mappings are key-value pairs, i.e. even nested mappings are compiled into dot-notation keys. Although, still a nested mapping can have an array as a value if it's a YAML array, i.e. YAML arrays are preserved and not converted into dot-notation.

### YAML Mapping Files

Mappings are saved in the mapping `directory` defined in the config. By default, it doesn't needs to be changed. But feel free to change it if needed. All the mapping files follow a basic structure, i.e. they all have `name`, `description` & `mappings`, where `mappings` is the object that contains all the mappings:

```yaml
---
# Descriptor
name: Generic descriptor mappings
description: Descriptor Mappings for all tenant types (generic)
mappings:
  example: one
  exampleTwo: two
  ...
```

```yaml
---
name: Settings for all tenant types (Generic)
description: These are generic settings for all the tenant types
mappings:
  fallback:
    - logo: https://example.com/images/logo.svg
    - currency: USD
  tracking:
    - active: yes
    - domain: null
  ...
```

### Compilation

Compilation can be made using artisan command:

```sh
php artisan mappings:compile
```

You can force clear old compilation values using `--force`. These comes handy during deployments to make sure the new mappings are added and the removed mappings are cleared.

Compilations can be cleared too using artisan command:

```sh
php artisan mappings:clear
```

### Fetching Mapping

Mappings can be fetched either by using mapping helpers or `Mapping` Facade.

#### Helper

Mappings can be fetched using helper methods. `descriptor()` helper returns `Illuminate\Support\Stringable` making it easier to manipulate strings.

```php
use Fourdotsix\TenancyMapping\Enums\MappingType;
...
// Getting a mapping value
$mapping = mapping(MappingType::Descriptor, 'institute'); // university

// Getting a Descriptor's Value
$descriptor = descriptor(key: 'learner', default: 'learner', i18n: false); // student
$descriptor->plural()->title(); // Students

// Getting a settings map value
$setting = settings_map(key: 'fallback.logo', default: 'https://example.com/example.png'); // (string) https://example.com/images/logo.svg
$track = settings_map(key: 'tracking.active', default: false, return_type: 'bool'); // (bool) true
```

#### Facade

Facade let's you fetch mapping values as-is, without any manipulations as oppose to manipulations done by helper functions. Facade resolves `\Fourdotsix\TenancyMapping\Mapping` making available all the methods too.

```php
use Fourdotsix\TenancyMapping\Facades\Mapping;
use Fourdotsix\TenancyMapping\Enums\MappingType;
...
// Fetch a specific mapping by key
$mapping = Mapping::get(type: MappingType::Descriptor, key: 'learner'); // student

// Get all the mappings for a Mapping Type
$mappings = Mapping::all(MappingType::Descriptor); // array of all the mappings

// Compile Mappings for a mapping type & tenant
Mapping::compile($tenant, MappingType::Settings);

// Clear compiled mappings for a specific mapping type
Mapping::clear(MappingType::Descriptor);
```

## Testing :construction:

> :construction: WIP

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Hash](https://github.com/secrethash)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## <br><br>

<p align="center">From the folks at <a href="https://fourdotsix.com" target="_blank">Four Dot Six (4.6)</a></p>
