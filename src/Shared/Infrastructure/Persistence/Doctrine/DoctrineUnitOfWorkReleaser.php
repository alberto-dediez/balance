<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\Persistence\UnitOfWorkReleaser;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineUnitOfWorkReleaser implements UnitOfWorkReleaser
{
    public function __construct(
        private EntityManagerInterface $manager,
    ) {}

    public function release(): void
    {
        $this->manager->clear();
    }
}
