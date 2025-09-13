<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryTest;

class InMemoryPropertyRepositoryTest extends PropertyRepositoryTest
{
    public function createSUT(): PropertyRepository
    {
        return PropertyRepositoryMother::inMemory();
    }
}
