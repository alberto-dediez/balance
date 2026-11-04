<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRegistered;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRemoved;
use App\Property\Domain\Model\DistributorAssignment\DistributorCannotDistributeItselfException;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class DistributorAssignmentTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterForAccountPublishesEvent(): void
    {
        $distributorAccountId = AccountId::generate();
        $accountId = AccountId::generate();
        $registeredBy = UserId::generate();

        $assignment = new DistributorAssignment(DistributorAssignmentId::generate());
        $assignment->registerForAccount($distributorAccountId, $accountId, $registeredBy);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRegistered::class, $lastEvent);
        $this->assertTrue($distributorAccountId->equals($lastEvent->distributorAccountId()));
        $this->assertNotNull($lastEvent->accountId());
        $this->assertTrue($accountId->equals($lastEvent->accountId()));
        $this->assertNull($lastEvent->propertyId());
        $this->assertTrue($registeredBy->equals($lastEvent->registeredBy()));
        $this->assertNotNull($assignment->accountId());
        $this->assertTrue($accountId->equals($assignment->accountId()));
        $this->assertNull($assignment->propertyId());
    }

    public function testRegisterForPropertyPublishesEvent(): void
    {
        $distributorAccountId = AccountId::generate();
        $propertyId = PropertyId::generate();

        $assignment = new DistributorAssignment(DistributorAssignmentId::generate());
        $assignment->registerForProperty($distributorAccountId, $propertyId, UserId::generate());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRegistered::class, $lastEvent);
        $this->assertNull($lastEvent->accountId());
        $this->assertNotNull($lastEvent->propertyId());
        $this->assertTrue($propertyId->equals($lastEvent->propertyId()));
        $this->assertNull($assignment->accountId());
        $this->assertNotNull($assignment->propertyId());
        $this->assertTrue($propertyId->equals($assignment->propertyId()));
    }

    public function testCannotDistributeItself(): void
    {
        $this->expectException(DistributorCannotDistributeItselfException::class);

        $accountId = AccountId::generate();

        $assignment = new DistributorAssignment(DistributorAssignmentId::generate());
        $assignment->registerForAccount($accountId, new AccountId((string) $accountId), UserId::generate());
    }

    public function testRemovePublishesEventWithRemovedByAndNotifyFlag(): void
    {
        $distributorAccountId = AccountId::generate();
        $propertyId = PropertyId::generate();
        $removedBy = UserId::generate();

        $assignment = DistributorAssignmentMother::forProperty($distributorAccountId, $propertyId);
        $assignment->remove($removedBy, true);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(DistributorAssignmentRemoved::class, $lastEvent);
        $this->assertTrue($removedBy->equals($lastEvent->removedBy()));
        $this->assertTrue($lastEvent->notifyDistributor());
        $this->assertTrue($distributorAccountId->equals($lastEvent->distributorAccountId()));
        $this->assertNotNull($lastEvent->propertyId());
        $this->assertTrue($propertyId->equals($lastEvent->propertyId()));
        $this->assertTrue($assignment->isRemoved());
        $this->assertNotNull($assignment->removedAt());

        $rebuilt = DistributorAssignmentRemoved::rebuildFromPayload($lastEvent->id(), $lastEvent->occurredOn(), $lastEvent->payload());

        $this->assertTrue($rebuilt->notifyDistributor());
        $this->assertNull($rebuilt->accountId());
    }

    public function testIsForProperty(): void
    {
        $this->assertTrue(DistributorAssignmentMother::forProperty(AccountId::generate(), PropertyId::generate())->isForProperty());
        $this->assertFalse(DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate())->isForProperty());
    }
}
