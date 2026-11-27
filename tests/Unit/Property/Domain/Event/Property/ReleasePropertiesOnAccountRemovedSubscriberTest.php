<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Event\Property;

use App\Property\Domain\Event\Property\ReleasePropertiesOnAccountRemovedSubscriber;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class ReleasePropertiesOnAccountRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToAccountRemoved(): void
    {
        $sut = $this->createSUT(PropertyRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new AccountRemoved(AccountId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(PropertyRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new AccountRegistered(
            accountId: AccountId::generate(),
            name: 'account-name',
            active: true,
        )));
    }

    public function testHandleReleasesPropertiesLinkedToAccount(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $otherAccountId = AccountId::generate();

        $otherCompanyId = CompanyId::generate();

        $property = PropertyMother::from(PropertyId::generate(), 'property-name', $accountId, companyId: CompanyId::generate(), numberOfEmployees: 25, dailyClients: 300);
        $repository->add($property);

        $other = PropertyMother::from(PropertyId::generate(), 'other-property-name', $otherAccountId, companyId: $otherCompanyId);
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new AccountRemoved($accountId));

        $this->assertCount(0, $repository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0)));

        $released = $repository->find($property->id());
        $this->assertNotNull($released);
        $this->assertNull($released->accountId());
        $this->assertNull($released->companyId());
        $this->assertSame(25, $released->numberOfEmployees());
        $this->assertSame(300, $released->dailyClients());

        $untouched = $repository->find($other->id());
        $this->assertNotNull($untouched);
        $this->assertNotNull($untouched->accountId());
        $this->assertTrue($otherAccountId->equals($untouched->accountId()));
        $this->assertNotNull($untouched->companyId());
        $this->assertTrue($otherCompanyId->equals($untouched->companyId()));
    }

    private function createSUT(PropertyRepository $propertyRepository): ReleasePropertiesOnAccountRemovedSubscriber
    {
        return new ReleasePropertiesOnAccountRemovedSubscriber($propertyRepository);
    }
}
