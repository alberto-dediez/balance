<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\EventOperations;

use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class EventOperationsContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    private function getProviderRepository(): ProviderRepository
    {
        return $this->client->getContainer()->get(ProviderRepository::class);
    }

    private function getEventRepository(): EventRepository
    {
        return $this->client->getContainer()->get(EventRepository::class);
    }

    #[Given('provider :name with id :id exists')]
    public function providerWithIdExists(string $name, string $id): void
    {
        $provider = ProviderMother::fromNameAndId($name, $id);

        $this->getProviderRepository()->add($provider);
    }

    #[Given('provider :name with id :id of account :account exists')]
    public function providerWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId($name, $account, $id);

        $this->getProviderRepository()->add($provider);
    }

    #[Given('event :name with id :id exists')]
    public function eventWithIdExists(string $name, string $id): void
    {
        $event = EventMother::fromNameAndId($name, $id);

        $this->getEventRepository()->add($event);
    }

    #[Given('event :name with id :id of account :account exists')]
    public function eventWithIdOfAccountExists(string $name, string $id, string $account): void
    {
        $event = EventMother::fromNameAndAccountAndId($name, $account, $id);

        $this->getEventRepository()->add($event);
    }

    #[Given('event :name with id :id of account :account with location :location exists')]
    public function eventWithIdOfAccountAndLocationExists(string $name, string $id, string $account, string $location): void
    {
        $event = EventMother::fromNameAndAccountAndIdAndLocation($name, $account, $id, $location);

        $this->getEventRepository()->add($event);
    }

    #[Then('event :id has venue location :label')]
    public function eventHasVenueLocation(string $id, string $label): void
    {
        $event = $this->getEventRepository()->find(new EventId($id));

        Assert::assertNotNull($event);
        Assert::assertSame($label, $event->venueLabel());
        Assert::assertTrue($event->hasVenueLocation());
    }

    #[Then('event named :name has venue location :label')]
    public function eventNamedHasVenueLocation(string $name, string $label): void
    {
        $events = $this->getEventRepository()->findByFilter(new EventFilter(name: $name));

        Assert::assertCount(1, $events);

        $event = reset($events);

        Assert::assertInstanceOf(Event::class, $event);
        Assert::assertSame($label, $event->venueLabel());
        Assert::assertTrue($event->hasVenueLocation());
    }
}
