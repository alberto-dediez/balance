<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryBaseYearDeclarationRepository;

class BaseYearDeclarationRepositoryMother
{
    public static function inMemory(): InMemoryBaseYearDeclarationRepository
    {
        return new InMemoryBaseYearDeclarationRepository();
    }
}
