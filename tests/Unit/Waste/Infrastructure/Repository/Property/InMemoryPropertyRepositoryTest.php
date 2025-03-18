<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Property;

use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryTest;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;

class InMemoryPropertyRepositoryTest extends PropertyRepositoryTest
{
    public function createSUT(): PropertyRepository
    {
        return PropertyRepositoryMother::inMemory();
    }
}