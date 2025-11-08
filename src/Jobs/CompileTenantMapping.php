<?php

namespace Fourdotsix\TenancyMapping\Jobs;

use Fourdotsix\TenancyMapping\Enums\MappingType;
use Fourdotsix\TenancyMapping\Mapping;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\Tenant;

class CompileTenantMapping implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public Tenant $tenant,
        public MappingType $mappingType,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(Mapping $mapping): void
    {
        $mapping->compile($this->tenant, $this->mappingType);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->tenant->id}_{$this->mappingType->value}";
    }
}
