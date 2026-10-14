<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Company\Update;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Application\Company\Update\UpdateCompanyRequest;
use App\Property\Application\Company\Update\UpdateCompanyService;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Company\CompanyUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateCompanyServiceTest extends TestCase
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

        $request = new UpdateCompanyRequest(
            companyId: $company->id(),
            name: 'Updated Name',
            vatNumber: 'B87654321',
            street: 'Calle Mayor 1',
            postalCode: '28001',
            city: 'Madrid',
            country: 'España',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals('Updated Name', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CompanyUpdated);
        $this->assertEquals('Updated Name', $lastEvent->name());
    }

    private function createSUT(CompanyRepository $repository): UpdateCompanyService
    {
        return new UpdateCompanyService(
            companyRepository: $repository,
            authorizer: new CompanyAuthorizer(),
        );
    }
}
