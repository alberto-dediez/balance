<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;

class PropertyRepositoryMother
{
    public static function inMemory(): InMemoryPropertyRepository
    {
        return new InMemoryPropertyRepository();
    }
}
