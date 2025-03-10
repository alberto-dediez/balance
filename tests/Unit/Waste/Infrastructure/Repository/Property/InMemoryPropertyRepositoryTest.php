<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Property;

use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryTest;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;

class InMemoryPropertyRepositoryTest extends PropertyRepositoryTest
{
    public function createSUT(): PropertyRepository
    {
        return new InMemoryPropertyRepository();
    }
}