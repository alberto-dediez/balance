<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\RecipientOrganization;

use App\FoodLoss\Domain\Event\RecipientOrganization\RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriber;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRemoved;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRecipientOrganizationRemoved(): void
    {
        $sut = new RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriber(DeliveryLogRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new RecipientOrganizationRemoved(RecipientOrganizationId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriber(DeliveryLogRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new DeliveryLogRemoved(DeliveryLogId::generate())));
    }

    public function testHandleRemovesDeliveryLogsAssociatedToRecipientOrganization(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();
        $recipientOrganizationId = RecipientOrganizationId::generate();
        $otherRecipientOrganizationId = RecipientOrganizationId::generate();

        $dl1 = DeliveryLogMother::withRecipientOrganizationId($recipientOrganizationId);
        $dl2 = DeliveryLogMother::withRecipientOrganizationId($recipientOrganizationId);
        $dl3 = DeliveryLogMother::withRecipientOrganizationId($otherRecipientOrganizationId);

        $repository->add($dl1);
        $repository->add($dl2);
        $repository->add($dl3);

        $sut = new RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriber($repository);
        $sut->handle(new RecipientOrganizationRemoved($recipientOrganizationId));

        $updatedDl1 = $repository->find($dl1->id());
        $updatedDl2 = $repository->find($dl2->id());
        $updatedDl3 = $repository->find($dl3->id());

        $this->assertNull($updatedDl1);
        $this->assertNull($updatedDl2);
        $this->assertNotNull($updatedDl3);
        $this->assertFalse($updatedDl3->isRemoved());
    }

    public function testHandleDoesNothingIfNoAssociatedDeliveryLogs(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();

        $dl = DeliveryLogMother::random();
        $repository->add($dl);

        $sut = new RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriber($repository);
        $sut->handle(new RecipientOrganizationRemoved(RecipientOrganizationId::generate()));

        $this->assertNotNull($repository->find($dl->id()));
        $this->assertFalse($repository->find($dl->id())->isRemoved());
    }
}
