<?php

declare(strict_types=1);

namespace App\Shared\Application\Persistence;

interface UnitOfWorkReleaser
{
    public function release(): void;
}
