<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RemoveActivityOnPropertyCarbonFootprintActivityRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveActivityOnPropertyCarbonFootprintActivityRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToActivityRemoved(): void
    {
        $sut = $this->createSUT($this->createMock(RemoveActivityService::class));

        $this->assertTrue($sut->isSubscribedTo($this->activityRemoved()));
        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
    }

    public function testHandleRemovesTheActivity(): void
    {
        $service = $this->createMock(RemoveActivityService::class);
        $service->expects($this->once())->method('__invoke');

        $this->createSUT($service)->handle($this->activityRemoved());
    }

    private function activityRemoved(): PropertyCarbonFootprintActivityRemoved
    {
        return new PropertyCarbonFootprintActivityRemoved(
            propertyCarbonFootprintId: PropertyCarbonFootprintId::generate(),
            activityId: ActivityId::generate(),
        );
    }

    private function createSUT(RemoveActivityService $service): RemoveActivityOnPropertyCarbonFootprintActivityRemovedSubscriber
    {
        return new RemoveActivityOnPropertyCarbonFootprintActivityRemovedSubscriber(
            $service,
            new FakeLoggedUser(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'))),
        );
    }
}
