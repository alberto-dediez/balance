<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory;

use App\Shared\Application\Persistence\UnitOfWorkReleaser;

class NullUnitOfWorkReleaser implements UnitOfWorkReleaser
{
    public function release(): void {}
}
