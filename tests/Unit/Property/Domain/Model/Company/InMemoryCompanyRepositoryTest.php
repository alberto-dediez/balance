<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Company;

use App\Property\Domain\Model\Company\CompanyRepository;

class InMemoryCompanyRepositoryTest extends CompanyRepositoryTest
{
    protected function createSUT(): CompanyRepository
    {
        return CompanyRepositoryMother::inMemory();
    }
}
