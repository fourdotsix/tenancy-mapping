<?php

namespace Fourdotsix\TenancyMapping\Commands;

use Fourdotsix\TenancyMapping\Enums\MappingType;
use Fourdotsix\TenancyMapping\Facades\Mapping;
use Fourdotsix\TenancyMapping\Validations\HasInputValidation;
use Illuminate\Console\Command;
use Laravel\Prompts\Progress;
use Stancl\Tenancy\Contracts\Tenant;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;

class MappingClearCommand extends Command
{
    use HasInputValidation;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mappings:clear {tenants?* : Tenants for which the mappings should be cleared}
                            {--t|type=* : Type of mappings to clear. By default `all` are cleared. Ex: `descriptor`}';

    /**
     * The console command description.
     */
    protected $description = 'Clear all the compiled mappings.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $types = $this->typeValidation(
            empty($this->option('type')) ?
            multiselect(
                'Which mappings should be cleared?',
                array_merge(
                    ['all' => 'Clear All'],
                    MappingType::toArray(),
                ),
                default: ['all'],
            ) :
            $this->option('type')
        );

        $tenants = $this->tenantValidation($this->argument('tenants'));

        $this->processClear($types, $tenants);
    }

    protected function processClear(array $types, array $tenantIds = [])
    {
        $model = config('tenancy.models.tenant');
        $tenants = $model::query();

        if (! empty($tenantIds)) {
            $tenants->whereIn('id', $tenantIds);
        }

        $progress = progress('Clearing Compiled Mappings', $tenants->count() * count($types));

        $progress->start();

        $tenants->chunk(
            100,
            fn ($t) => $t->each(
                fn (Tenant $tenant) => $tenant->run(fn () => $this->clearCompiled($types, $tenant, $progress))
            )
        );

        $progress->finish();

    }

    /**
     * Clear the compiled mappings
     *
     * @return void
     */
    protected function clearCompiled(array $types, Tenant $tenant, Progress $progress)
    {
        foreach ($types as $type) {
            $progress->label("Clearing compiled [{$type}] mappings for tenant [{$tenant->id}]");
            Mapping::clear(MappingType::from($type));
            $progress->hint("Cleared compiled mapping for [{$type}]");
            $progress->advance();
        }
    }
}
