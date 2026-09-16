<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\Activity\Remove\RemoveActivityService;
use App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint\RemoveActivityOnCompanyCarbonFootprintActivityRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveActivityOnCompanyCarbonFootprintActivityRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToActivityRemoved(): void
    {
        $sut = $this->createSUT($this->createMock(RemoveActivityService::class));

        $this->assertTrue($sut->isSubscribedTo($this->activityRemoved()));
        $this->assertFalse($sut->isSubscribedTo(new CompanyCarbonFootprintRemoved(CompanyCarbonFootprintId::generate())));
    }

    public function testHandleRemovesTheActivity(): void
    {
        $service = $this->createMock(RemoveActivityService::class);
        $service->expects($this->once())->method('__invoke');

        $this->createSUT($service)->handle($this->activityRemoved());
    }

    private function activityRemoved(): CompanyCarbonFootprintActivityRemoved
    {
        return new CompanyCarbonFootprintActivityRemoved(
            companyCarbonFootprintId: CompanyCarbonFootprintId::generate(),
            activityId: ActivityId::generate(),
        );
    }

    private function createSUT(RemoveActivityService $service): RemoveActivityOnCompanyCarbonFootprintActivityRemovedSubscriber
    {
        return new RemoveActivityOnCompanyCarbonFootprintActivityRemovedSubscriber(
            $service,
            new FakeLoggedUser(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'))),
        );
    }
}
