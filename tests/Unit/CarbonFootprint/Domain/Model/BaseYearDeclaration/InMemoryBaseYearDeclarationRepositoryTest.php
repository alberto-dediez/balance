<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;

class InMemoryBaseYearDeclarationRepositoryTest extends BaseYearDeclarationRepositoryTest
{
    protected function createSUT(): BaseYearDeclarationRepository
    {
        return BaseYearDeclarationRepositoryMother::inMemory();
    }
}
