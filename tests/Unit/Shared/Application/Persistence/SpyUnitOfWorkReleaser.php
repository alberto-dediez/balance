<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Persistence;

use App\Shared\Application\Persistence\UnitOfWorkReleaser;

class SpyUnitOfWorkReleaser implements UnitOfWorkReleaser
{
    public int $calls = 0;

    public function release(): void
    {
        ++$this->calls;
    }
}
