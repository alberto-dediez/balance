<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryTest;

class InMemoryDestinationRepositoryTest extends DestinationRepositoryTest
{
    public function createSUT(): DestinationRepository
    {
        return DestinationRepositoryMother::inMemory();
    }
}
