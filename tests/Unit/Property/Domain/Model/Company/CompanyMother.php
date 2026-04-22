<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Company;

use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;

class CompanyMother
{
    public static function registeredCompany(): Company
    {
        return self::fromName('Company');
    }

    public static function fromName(string $name): Company
    {
        return self::from(
            name: $name,
            id: (string) CompanyId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Company
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromNameAndAccountAndId(string $name, string $accountId, string $id): Company
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );
    }

    public static function from(
        string $name,
        string $id,
        string $vatNumber = 'B12345678',
        ?string $accountId = null,
    ): Company {
        $company = new Company(
            id: new CompanyId($id),
        );
        $company->register(
            name: $name,
            vatNumber: $vatNumber,
            address: null,
            accountId: null !== $accountId ? new AccountId($accountId) : null,
        );

        return $company;
    }

    public static function random(): Company
    {
        return self::from(
            name: 'company-' . rand(100, 999),
            id: (string) CompanyId::generate(),
        );
    }
}
