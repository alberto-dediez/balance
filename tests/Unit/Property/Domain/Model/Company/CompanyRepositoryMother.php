<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Company;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;

class CompanyRepositoryMother
{
    public static function inMemory(): InMemoryCompanyRepository
    {
        return new InMemoryCompanyRepository();
    }
}
