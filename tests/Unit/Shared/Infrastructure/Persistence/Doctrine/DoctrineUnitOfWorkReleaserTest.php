<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineUnitOfWorkReleaser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DoctrineUnitOfWorkReleaserTest extends TestCase
{
    public function testReleaseClearsTheEntityManager(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('clear');

        (new DoctrineUnitOfWorkReleaser($manager))->release();
    }
}
