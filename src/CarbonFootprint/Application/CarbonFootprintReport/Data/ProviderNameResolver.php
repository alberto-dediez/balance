<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;

final class ProviderNameResolver
{
    /** @var array<string, string> */
    private array $namesById = [];

    public function __construct(private ProviderRepository $providers) {}

    public function resolve(ProviderId $id): string
    {
        $key = (string) $id;

        if (!isset($this->namesById[$key])) {
            $this->namesById[$key] = $this->providers->find($id)?->name() ?? $key;
        }

        return $this->namesById[$key];
    }

    public function reset(): void
    {
        $this->namesById = [];
    }
}
