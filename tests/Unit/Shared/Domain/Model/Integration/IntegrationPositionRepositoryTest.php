<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationPosition;
use App\Shared\Domain\Model\Integration\IntegrationPositionRepository;
use PHPUnit\Framework\TestCase;

abstract class IntegrationPositionRepositoryTest extends TestCase
{
    abstract protected function createSUT(): IntegrationPositionRepository;

    public function testSaveAndFind(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->find('waste', 'P1'));

        $repository->save(new IntegrationPosition('waste', 'P1', 10, new \DateTimeImmutable('NOW')));
        $repository->save(new IntegrationPosition('balance', 'P1', 3, new \DateTimeImmutable('NOW')));

        $this->assertSame(10, $repository->find('waste', 'P1')?->lastSequence());
        $this->assertSame(3, $repository->find('balance', 'P1')?->lastSequence());
        $this->assertNull($repository->find('waste', 'P2'));
    }

    public function testSaveUpdatesExistingPosition(): void
    {
        $repository = $this->createSUT();
        $position = new IntegrationPosition('waste', 'P1', 10, new \DateTimeImmutable('NOW'));
        $repository->save($position);

        $position->advance(12, new \DateTimeImmutable('NOW'));
        $repository->save($position);

        $this->assertSame(12, $repository->find('waste', 'P1')?->lastSequence());
    }
}
