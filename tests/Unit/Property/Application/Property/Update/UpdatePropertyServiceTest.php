<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\Update;

use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Application\Property\PropertyPlaceLocationSetter;
use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Application\Property\Update\UpdatePropertyService;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyUpdated;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class UpdatePropertyServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $request = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'Hotel',
            accountId: new AccountId('6666'),
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PropertyUpdated);
        $this->assertEquals('Hotel', $lastEvent->name());
        $this->assertEquals('6666', (string) $lastEvent->accountId());
        $this->assertTrue($propertyPersisted->id()->equals($lastEvent->propertyId()));
        $this->assertSame(OperatingRegime::Owned, $propertyPersisted->operatingRegime());
        $this->assertSame(OperatingRegime::Owned, $result->operatingRegime);
    }

    public function testUpdateOperatingRegime(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: null,
            operatingRegime: OperatingRegime::LeasedOut,
        ), $user);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertSame(OperatingRegime::LeasedOut, $propertyPersisted->operatingRegime());
        $this->assertSame(OperatingRegime::LeasedOut, $result->operatingRegime);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(PropertyUpdated::class, $lastEvent);
        $this->assertSame(OperatingRegime::LeasedOut, $lastEvent->operatingRegime());
    }

    public function testUpdateWithoutOperatingRegimeKeepsTheCurrentOne(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');
        $property->update(name: 'MyProperty', accountId: null, operatingRegime: OperatingRegime::Franchised);

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: new AccountId('6666'),
        ), $user);

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertSame(OperatingRegime::Franchised, $propertyPersisted->operatingRegime());
        $this->assertSame(OperatingRegime::Franchised, $result->operatingRegime);
    }

    public function testUpdateOperatorContact(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');

        $repository->add($property);

        $result = $this->createSUT($repository)(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: null,
            operatorContact: OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::Spanish),
        ), UserMother::fromUsernameAndRole('user', Role::Admin));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertSame('franquicia@hotel.es', $propertyPersisted->operatorContact()->email());
        $this->assertSame('franquicia@hotel.es', $result->operatorContact?->email());
    }

    public function testUpdateWithAnEmptyOperatorContactClearsIt(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');
        $property->updateOperatorContact(OperatorContact::from('franquicia@hotel.es', 'Ana', null));

        $repository->add($property);

        $result = $this->createSUT($repository)(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: null,
            operatorContact: new OperatorContact(),
        ), UserMother::fromUsernameAndRole('user', Role::Admin));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertTrue($propertyPersisted->operatorContact()->isEmpty());
    }

    public function testUpdateWithoutOperatorContactKeepsTheCurrentOne(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::fromName('MyProperty');
        $property->updateOperatorContact(OperatorContact::from('franquicia@hotel.es', 'Ana', null));

        $repository->add($property);

        $result = $this->createSUT($repository)(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'MyProperty',
            accountId: null,
        ), UserMother::fromUsernameAndRole('user', Role::Admin));

        $propertyPersisted = $repository->find($result->propertyId);

        $this->assertNotNull($propertyPersisted);
        $this->assertSame('franquicia@hotel.es', $propertyPersisted->operatorContact()->email());
    }

    public function testReleasingAccountOfPropertyWithoutAccountButWithCompanyDropsTheCompany(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $property = PropertyMother::from(PropertyId::generate(), 'Hotel', accountId: null, companyId: CompanyId::generate(), numberOfEmployees: 25, dailyClients: 300);

        $repository->add($property);

        $sut = $this->createSUT($repository);

        $request = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: null,
            info: $property->info(),
            externalId: $property->externalId(),
            companyId: null,
            numberOfEmployees: $property->numberOfEmployees(),
            dailyClients: $property->dailyClients(),
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $propertyPersisted = $repository->find($property->id());

        $this->assertNotNull($propertyPersisted);
        $this->assertNull($propertyPersisted->accountId());
        $this->assertNull($propertyPersisted->companyId());
        $this->assertSame(25, $propertyPersisted->numberOfEmployees());
        $this->assertSame(300, $propertyPersisted->dailyClients());
    }

    public function testSameAccountKeepsCompanyEmployeesAndDailyClients(): void
    {
        $repository = PropertyRepositoryMother::inMemory();
        $companyRepository = CompanyRepositoryMother::inMemory();

        $company = CompanyMother::fromNameAndAccountAndId('Company', 'account-a', 'company-a');

        $companyRepository->add($company);

        $property = PropertyMother::from(PropertyId::generate(), 'Hotel', accountId: new AccountId('account-a'), companyId: $company->id(), numberOfEmployees: 25, dailyClients: 300);

        $repository->add($property);

        $sut = $this->createSUT($repository, $companyRepository);

        $request = new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: new AccountId('account-a'),
            info: $property->info(),
            externalId: $property->externalId(),
            companyId: $property->companyId(),
            numberOfEmployees: $property->numberOfEmployees(),
            dailyClients: $property->dailyClients(),
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $propertyPersisted = $repository->find($property->id());

        $this->assertNotNull($propertyPersisted);
        $this->assertNotNull($propertyPersisted->accountId());
        $this->assertTrue($propertyPersisted->accountId()->equals(new AccountId('account-a')));
        $this->assertNotNull($propertyPersisted->companyId());
        $this->assertTrue($propertyPersisted->companyId()->equals($company->id()));
        $this->assertSame(25, $propertyPersisted->numberOfEmployees());
        $this->assertSame(300, $propertyPersisted->dailyClients());
    }

    public function testIsHandle(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $property = PropertyMother::random();

        $result = $sut->isHandled(new UpdatePropertyRequest(
            propertyId: $property->id(),
            name: 'Restaurante',
            accountId: AccountId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryPropertyRepository $repository, ?InMemoryCompanyRepository $companyRepository = null): UpdatePropertyService
    {
        return new UpdatePropertyService($repository, $companyRepository ?? CompanyRepositoryMother::inMemory(), new PropertyAuthorizer(), new PropertyPlaceLocationSetter(new InMemoryPlaceFinder()));
    }
}
