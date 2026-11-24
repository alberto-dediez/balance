<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Event\Company;

use App\Property\Domain\Event\Company\UnlinkCompanyOnCompanyRemovedSubscriber;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class UnlinkCompanyOnCompanyRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToCompanyRemoved(): void
    {
        $sut = $this->createSUT(PropertyRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new CompanyRemoved(CompanyId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(PropertyRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new AccountRemoved(AccountId::generate())));
    }

    public function testHandleUnlinksCompanyKeepingAccountEmployeesAndDailyClients(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $companyId = CompanyId::generate();
        $otherCompanyId = CompanyId::generate();

        $property = PropertyMother::from(PropertyId::generate(), 'property-name', $accountId, companyId: $companyId, numberOfEmployees: 25, dailyClients: 300);
        $repository->add($property);

        $other = PropertyMother::from(PropertyId::generate(), 'other-property-name', $accountId, companyId: $otherCompanyId, numberOfEmployees: 10, dailyClients: 50);
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new CompanyRemoved($companyId));

        $unlinked = $repository->find($property->id());
        $this->assertNotNull($unlinked);
        $this->assertNull($unlinked->companyId());
        $this->assertNotNull($unlinked->accountId());
        $this->assertTrue($accountId->equals($unlinked->accountId()));
        $this->assertSame(25, $unlinked->numberOfEmployees());
        $this->assertSame(300, $unlinked->dailyClients());

        $untouched = $repository->find($other->id());
        $this->assertNotNull($untouched);
        $this->assertNotNull($untouched->companyId());
        $this->assertTrue($otherCompanyId->equals($untouched->companyId()));
        $this->assertSame(10, $untouched->numberOfEmployees());
        $this->assertSame(50, $untouched->dailyClients());
    }

    private function createSUT(PropertyRepository $propertyRepository): UnlinkCompanyOnCompanyRemovedSubscriber
    {
        return new UnlinkCompanyOnCompanyRemovedSubscriber($propertyRepository);
    }
}
