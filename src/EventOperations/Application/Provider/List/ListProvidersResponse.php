<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\List;

class ListProvidersResponse
{
    /** @param array<\App\EventOperations\Domain\Model\Provider\Provider> $providers */
    public function __construct(public array $providers = [], public int $totalResults = 0) {}
}
