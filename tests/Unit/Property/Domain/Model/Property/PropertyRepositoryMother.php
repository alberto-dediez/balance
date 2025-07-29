<?php

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Infrastructure\Repository\Property\InMemoryPropertyRepository;

class PropertyRepositoryMother
{
    public static function inMemory(): InMemoryPropertyRepository
    {
        return new InMemoryPropertyRepository();
    }
}