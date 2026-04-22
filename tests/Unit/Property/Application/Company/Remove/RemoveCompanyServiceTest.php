<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Company\Remove;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Application\Company\Remove\RemoveCompanyRequest;
use App\Property\Application\Company\Remove\RemoveCompanyService;
use App\Property\Domain\Model\Company\CompanyRemoved;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveCompanyServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = CompanyRepositoryMother::inMemory();

        $company = CompanyMother::registeredCompany();

        $repository->add($company);

        $sut = $this->createSUT($repository);

        $sut(new RemoveCompanyRequest($company->id()), UserMother::adminUser());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CompanyRemoved);
        $this->assertTrue($company->id()->equals($lastEvent->companyId()));
    }

    private function createSUT(CompanyRepository $repository): RemoveCompanyService
    {
        return new RemoveCompanyService(
            repository: $repository,
            authorizer: new CompanyAuthorizer(),
        );
    }
}
