<?php

namespace Fourdotsix\TenancyMapping\Commands;

use Fourdotsix\TenancyMapping\Enums\MappingType;
use Fourdotsix\TenancyMapping\Facades\Mapping;
use Fourdotsix\TenancyMapping\Jobs\CompileTenantMapping;
use Fourdotsix\TenancyMapping\Validations\HasInputValidation;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Laravel\Prompts\Progress;
use Stancl\Tenancy\Contracts\Tenant;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\progress;

class MappingCompileCommand extends Command implements Isolatable
{
    use HasInputValidation;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mappings:compile {tenants?* : Tenants for which the mappings should be compiled}
                            {--t|type=* : Type of mappings to compile. By default `all` are compiled. Ex: `descriptor`}
                            {--Q|queue : Queue the compilation process}
                            {--f|force : Clear old compilations and compile again}';

    /**
     * The console command description.
     */
    protected $description = 'Compile mappings for each tenants.';

    /**
     * Determines if the job should be queued
     */
    protected bool $shouldQueue = false;

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
                'Which mappings should be compiled?',
                array_merge(
                    ['all' => 'Compile All'],
                    MappingType::toArray(),
                ),
                default: ['all'],
            ) :
            $this->option('type')
        );

        $tenants = $this->tenantValidation($this->argument('tenants'));

        $this->shouldQueue = $this->option('queue');

        if ($this->option('force')) {
            $this->call('mappings:clear', [
                '--type' => $types,
                'tenants' => $tenants,
            ]);
        }

        $this->processCompilation($types, $tenants);
    }

    /**
     * Initiate the compilation process
     *
     * @return void
     */
    protected function processCompilation(array $types, array $tenantIds = [])
    {
        $model = config('tenancy.models.tenant');
        $query = $model::active()->withoutEagerLoads();

        if (! empty($tenantIds)) {
            $query->whereIn('id', $tenantIds);
        }

        $progress = progress('Compiling Mappings', $query->count() * count($types));

        $progress->start();

        $query->chunk(
            100,
            fn ($tenants) => $tenants->each(
                fn (Tenant $tenant) => $tenant->run(fn () => $this->compile($types, $tenant, $progress))
            )
        );

        $progress->finish();
    }

    /**
     * Compile the mappings
     *
     * @return void
     */
    protected function compile(array $types, Tenant $tenant, Progress $progress)
    {
        foreach ($types as $type) {
            $progress->label("Compiling [{$type}] mappings for tenant [{$tenant->id}]");
            try {
                if ($this->shouldQueue) {
                    dispatch(new CompileTenantMapping($tenant, MappingType::from($type)));
                } else {
                    /** @var object{name: string, description: string} $map */
                    $map = Mapping::compile($tenant, MappingType::from($type));
                    $progress->hint("Compiled [{$map->name}]. {$map->description}");
                }
            } catch (\Throwable $t) {
                $this->components->error("Compilation Failed for mapping: [{$type}] for tenant [{$tenant->id}]");
                throw $t;
            }
            $progress->advance();
        }
    }
}
