<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Destination;

use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryTest;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;

class InMemoryDestinationRepositoryTest extends DestinationRepositoryTest
{
    public function createSUT(): DestinationRepository
    {
        return DestinationRepositoryMother::inMemory();
    }
}