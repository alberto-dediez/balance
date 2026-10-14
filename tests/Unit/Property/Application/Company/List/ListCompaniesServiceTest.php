<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Company\List;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Application\Company\List\ListCompaniesRequest;
use App\Property\Application\Company\List\ListCompaniesService;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCompaniesServiceTest extends TestCase
{
    public function testUseCase(): void
    {
        $repository = CompanyRepositoryMother::inMemory();

        $repository->add(CompanyMother::fromName('Empresa A'));
        $repository->add(CompanyMother::fromName('Empresa B'));
        $repository->add(CompanyMother::fromName('Empresa C'));

        $sut = $this->createSUT($repository);

        $result = $sut(new ListCompaniesRequest(), UserMother::adminUser());

        $this->assertCount(3, $result->companies);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(CompanyRepository $repository): ListCompaniesService
    {
        return new ListCompaniesService(
            repository: $repository,
            authorizer: new CompanyAuthorizer(),
        );
    }
}
