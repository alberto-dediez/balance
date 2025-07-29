<?php

namespace App\Tests\Unit\Property\Domain\Model\Destination;

use App\Property\Infrastructure\Repository\Destination\InMemoryDestinationRepository;

class DestinationRepositoryMother
{
    public static function inMemory(): InMemoryDestinationRepository
    {
        return new InMemoryDestinationRepository();
    }
}