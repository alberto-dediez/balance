<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Property;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryTest;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;

class InMemoryPropertyRepositoryTest extends PropertyRepositoryTest
{
    public function createSUT(): PropertyRepository
    {
        return PropertyRepositoryMother::inMemory();
    }
}