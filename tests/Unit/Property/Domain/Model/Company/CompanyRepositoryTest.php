<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Company;

use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CompanyRepositoryTest extends KernelTestCase
{
    public function testAddCompanyToRepository(): void
    {
        $repository = $this->createSUT();

        $company = CompanyMother::registeredCompany();

        $this->assertNull($repository->find($company->id()));

        $repository->add($company);

        $recoveredCompany = $repository->find($company->id());

        $this->assertNotNull($recoveredCompany);
        $this->assertEquals($company->id(), $recoveredCompany->id());
    }

    public function testRemoveCompanyFromRepository(): void
    {
        $repository = $this->createSUT();

        $company = CompanyMother::registeredCompany();

        $repository->add($company);

        $recoveredCompany = $repository->find($company->id());

        $this->assertNotNull($recoveredCompany);
        $this->assertEquals($company->id(), $recoveredCompany->id());

        $repository->remove($company);

        $this->assertNull($repository->find($company->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::fromName('Empresa específica'));

        $filter = new CompanyFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new CompanyFilter(
            name: 'Empresa específica',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::random());
        $repository->add(CompanyMother::fromName('Empresa específica'));

        $filter = new CompanyFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $company = CompanyMother::registeredCompany();

        $repository->add($company);

        $recoveredCompany = $repository->find($company->id());

        $this->assertNotNull($recoveredCompany);

        $recoveredCompany->remove();

        $repository->add($company);

        $this->assertNull($repository->find($company->id()));
    }

    abstract protected function createSUT(): CompanyRepository;
}
