<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Company\Register;

use App\Property\Application\Company\CompanyAuthorizer;
use App\Property\Application\Company\Register\RegisterCompanyRequest;
use App\Property\Application\Company\Register\RegisterCompanyService;
use App\Property\Domain\Model\Company\CompanyRegistered;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterCompanyServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $request = new RegisterCompanyRequest(
            name: 'Empresa Test',
            vatNumber: 'B12345678',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->companyId);
        $this->assertEquals(36, strlen((string) $result->companyId));
        $this->assertEquals('Empresa Test', $result->name);

        $companyPersisted = $repository->find($result->companyId);

        $this->assertNotNull($companyPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CompanyRegistered);
        $this->assertEquals('Empresa Test', $lastEvent->name());
        $this->assertTrue($companyPersisted->id()->equals($lastEvent->companyId()));
    }

    public function testIsHandle(): void
    {
        $repository = CompanyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterCompanyRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CompanyRepository $repository): RegisterCompanyService
    {
        return new RegisterCompanyService(
            companyRepository: $repository,
            authorizer: new CompanyAuthorizer(),
        );
    }
}
