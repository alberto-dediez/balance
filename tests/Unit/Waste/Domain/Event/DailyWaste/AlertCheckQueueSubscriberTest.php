<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Event\DailyWaste;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Waste\Application\AlertCheckQueue\Register\RegisterAlertCheckQueueRequest;
use App\Waste\Domain\Event\DailyWaste\AlertCheckQueueSubscriber;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRegistered;
use App\Waste\Domain\Model\DailyWaste\DailyWasteUpdated;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class AlertCheckQueueSubscriberTest extends TestCase
{
    private AlertCheckQueueSubscriber $sut;
    private SluttyCommandHandler $spyCommandBus;
    private AccountId $accountId;
    private \DateTimeImmutable $day;
    private DailyWasteRegistered $registeredEvent;
    private DailyWasteUpdated $updatedEvent;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();

        $this->accountId = new AccountId('account-id-test');
        $this->day = new \DateTimeImmutable('2024-01-15');

        $this->registeredEvent = new DailyWasteRegistered(
            dailyWasteId: DailyWasteId::generate(),
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            purpose: Purpose::Waste,
            wasteManagerId: WasteManagerId::generate(),
            accountId: $this->accountId,
            day: $this->day,
            load: 10.5,
        );

        $this->updatedEvent = new DailyWasteUpdated(
            dailyWasteId: DailyWasteId::generate(),
            accountId: $this->accountId,
            day: $this->day,
            load: 20.0,
        );

        $this->sut = new AlertCheckQueueSubscriber(
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }

    public function testIsSubscribedToDailyWasteRegistered(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->registeredEvent));
    }

    public function testIsSubscribedToDailyWasteUpdated(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->updatedEvent));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new PropertyRemoved(PropertyId::generate())));
    }

    public function testHandleDailyWasteRegisteredDispatchesCommand(): void
    {
        $this->sut->handle($this->registeredEvent);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(RegisterAlertCheckQueueRequest::class, $commands[0]['command']);

        /** @var RegisterAlertCheckQueueRequest $request */
        $request = $commands[0]['command'];
        $this->assertTrue($this->accountId->equals($request->accountId));
        $this->assertEquals($this->day->format('Y-m-d'), $request->checkDate->format('Y-m-d'));
    }

    public function testHandleDailyWasteUpdatedDispatchesCommand(): void
    {
        $this->sut->handle($this->updatedEvent);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(RegisterAlertCheckQueueRequest::class, $commands[0]['command']);

        /** @var RegisterAlertCheckQueueRequest $request */
        $request = $commands[0]['command'];
        $this->assertTrue($this->accountId->equals($request->accountId));
        $this->assertEquals($this->day->format('Y-m-d'), $request->checkDate->format('Y-m-d'));
    }
}
