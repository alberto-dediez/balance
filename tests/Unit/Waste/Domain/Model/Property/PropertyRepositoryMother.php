<?php

namespace App\Tests\Unit\Waste\Domain\Model\Property;

use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;

class PropertyRepositoryMother
{
    public static function inMemory(): InMemoryPropertyRepository
    {
        return new InMemoryPropertyRepository();
    }
}