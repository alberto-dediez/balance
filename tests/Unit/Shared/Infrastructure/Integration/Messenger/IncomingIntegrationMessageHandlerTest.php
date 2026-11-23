<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration\Messenger;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Integration\IntegrationEventHandler;
use App\Shared\Application\Integration\Ping\PlatformPinged;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Integration\IntegrationInboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationInboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationInboxStatus;
use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationPosition;
use App\Shared\Infrastructure\Integration\BufferedIntegrationEventPublisher;
use App\Shared\Infrastructure\Integration\IntegrationContext;
use App\Shared\Infrastructure\Integration\IntegrationEnvelope;
use App\Shared\Infrastructure\Integration\IntegrationSettings;
use App\Shared\Infrastructure\Integration\IntegrationUserNotAvailable;
use App\Shared\Infrastructure\Integration\IntegrationUserProvider;
use App\Shared\Infrastructure\Integration\Messenger\IncomingIntegrationMessage;
use App\Shared\Infrastructure\Integration\Messenger\IncomingIntegrationMessageHandler;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationInboxRepository;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationPositionRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\FakeEvent;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\ErrorReport\SpyLogger;
use App\Tests\Unit\Shared\Infrastructure\Integration\IntegrationEnvelopeMother;
use App\Tests\Unit\Shared\Infrastructure\Integration\IntegrationSettingsMother;
use App\Tests\Unit\Shared\Infrastructure\Messenger\SpyLoggedAccount;
use App\Tests\Unit\Shared\Infrastructure\Messenger\SpyLoggedUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class IncomingIntegrationMessageHandlerTest extends TestCase
{
    private InMemoryIntegrationInboxRepository $inbox;
    private InMemoryIntegrationPositionRepository $positions;
    private InMemoryIntegrationOutboxRepository $outbox;
    private InMemoryUserRepository $userRepository;
    private IntegrationContext $context;
    private BufferedIntegrationEventPublisher $publisher;
    private SpyLoggedUser $loggedUser;
    private SpyLoggedAccount $loggedAccount;
    private SpyIntegrationEventHandler $handler;
    private User $integrationUser;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        DomainEventPublisher::instance()->clearPending();

        $this->inbox = new InMemoryIntegrationInboxRepository();
        $this->positions = new InMemoryIntegrationPositionRepository();
        $this->outbox = new InMemoryIntegrationOutboxRepository();
        $this->userRepository = new InMemoryUserRepository();
        $this->context = new IntegrationContext();
        $this->publisher = new BufferedIntegrationEventPublisher(
            IntegrationSettingsMother::enabled(),
            $this->context,
            $this->outbox,
            new SpyLogger(),
        );
        $this->loggedUser = new SpyLoggedUser();
        $this->loggedAccount = new SpyLoggedAccount();
        $this->handler = new SpyIntegrationEventHandler($this->context);
        $this->integrationUser = UserMother::fromUsernameAndRole('integration', Role::Admin);
        $this->userRepository->add($this->integrationUser);
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
        DomainEventPublisher::instance()->clearPending();
    }

    public function testDisabledDoesNothing(): void
    {
        $envelope = IntegrationEnvelopeMother::from();

        $this->createSUT(settings: IntegrationSettingsMother::disabled())($this->message($envelope));

        $this->assertSame([], $this->handler->calls());
        $this->assertFalse($this->inbox->has($envelope->id));
    }

    public function testOwnSourceIsIgnoredWithoutInbox(): void
    {
        $envelope = IntegrationEnvelopeMother::from(source: 'biohydra');

        $this->createSUT()($this->message($envelope));

        $this->assertSame([], $this->handler->calls());
        $this->assertFalse($this->inbox->has($envelope->id));
    }

    public function testDuplicateDoesNotRunHandlers(): void
    {
        $envelope = IntegrationEnvelopeMother::from();
        $sut = $this->createSUT();

        $sut($this->message($envelope));
        $sut($this->message($envelope));

        $this->assertCount(1, $this->handler->calls());
        $this->assertCount(1, $this->inbox->list(new IntegrationInboxFilter(limit: 0)));
    }

    public function testExceededHopsIsSkipped(): void
    {
        $envelope = IntegrationEnvelopeMother::from(hops: 6);

        $this->createSUT()($this->message($envelope));

        $this->assertSame([], $this->handler->calls());
        $this->assertSame(IntegrationInboxStatus::SkippedHops, $this->inboxMessage($envelope->id)->status());
    }

    public function testUnknownTypeIsSkipped(): void
    {
        $envelope = IntegrationEnvelopeMother::from(type: 'property.registered');

        $this->createSUT()($this->message($envelope));

        $this->assertSame([], $this->handler->calls());
        $this->assertSame(IntegrationInboxStatus::SkippedUnknownType, $this->inboxMessage($envelope->id)->status());
    }

    public function testStaleSequenceIsSkipped(): void
    {
        $this->positions->save(new IntegrationPosition('waste', 'P1', 12, new \DateTimeImmutable('NOW')));
        $envelope = IntegrationEnvelopeMother::from(sequence: 11, aggregateId: 'P1');

        $this->createSUT()($this->message($envelope));

        $this->assertSame([], $this->handler->calls());
        $this->assertSame(IntegrationInboxStatus::SkippedStale, $this->inboxMessage($envelope->id)->status());
        $this->assertSame(12, $this->positions->find('waste', 'P1')?->lastSequence());
    }

    public function testSuccessRunsHandlersAndRecordsTheMessage(): void
    {
        $envelope = IntegrationEnvelopeMother::from(sequence: 10, aggregateId: 'P1');

        $this->createSUT()($this->message($envelope));

        $calls = $this->handler->calls();
        $this->assertCount(1, $calls);
        $this->assertSame($envelope->id, $calls[0]['event']->id);
        $this->assertSame($this->integrationUser, $calls[0]['user']);
        $this->assertSame($envelope->id, $calls[0]['context']?->id);
        $this->assertNull($this->context->current());
        $this->assertSame(IntegrationInboxStatus::Processed, $this->inboxMessage($envelope->id)->status());
        $this->assertSame(10, $this->positions->find('waste', 'P1')?->lastSequence());
        $this->assertSame([$this->integrationUser, null], $this->loggedUser->forcedUserCalls());
        $this->assertGreaterThanOrEqual(2, $this->loggedAccount->resetCalls());
    }

    public function testSuccessAdvancesAnExistingPosition(): void
    {
        $this->positions->save(new IntegrationPosition('waste', 'P1', 10, new \DateTimeImmutable('NOW')));
        $envelope = IntegrationEnvelopeMother::from(sequence: 12, aggregateId: 'P1');

        $this->createSUT()($this->message($envelope));

        $this->assertSame(12, $this->positions->find('waste', 'P1')?->lastSequence());
    }

    public function testDomainEventsRaisedByHandlersArePublishedAfterThem(): void
    {
        $subscriber = new SpyDomainEventSubscriber();
        $eventsSeenDuringHandle = null;
        $handler = new SpyIntegrationEventHandler($this->context, onHandle: function () use ($subscriber, &$eventsSeenDuringHandle): void {
            DomainEventPublisher::instance()->publish(new FakeEvent());
            $eventsSeenDuringHandle = count($subscriber->events());
        });

        $this->createSUT(handlers: [$handler])($this->message(IntegrationEnvelopeMother::from()));

        $this->assertSame(0, $eventsSeenDuringHandle);
        $this->assertCount(1, $subscriber->events());
    }

    public function testIntegrationEventPublishedFromHandlerReachesTheOutboxWithCausation(): void
    {
        $envelope = IntegrationEnvelopeMother::from(hops: 1);
        $handler = new SpyIntegrationEventHandler($this->context, onHandle: function (): void {
            $this->publisher->publish(new PlatformPinged('pong'));
        });

        $this->createSUT(handlers: [$handler])($this->message($envelope));

        $outbox = $this->outbox->list(new IntegrationOutboxFilter(limit: 0));
        $this->assertCount(1, $outbox);
        $this->assertSame($envelope->id, $outbox[0]->causationId());
        $this->assertSame($envelope->correlationId, $outbox[0]->correlationId());
        $this->assertSame(2, $outbox[0]->hops());
    }

    public function testFailingHandlerLeavesNothingBehind(): void
    {
        $subscriber = new SpyDomainEventSubscriber();
        $envelope = IntegrationEnvelopeMother::from(sequence: 10, aggregateId: 'P1');
        $handler = new SpyIntegrationEventHandler($this->context, onHandle: function (): void {
            $this->publisher->publish(new PlatformPinged('pong'));
            DomainEventPublisher::instance()->publish(new FakeEvent());

            throw new \RuntimeException('Handler failed');
        });

        try {
            $this->createSUT(handlers: [$handler])($this->message($envelope));
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Handler failed', $exception->getMessage());
        }

        $this->assertFalse($this->inbox->has($envelope->id));
        $this->assertNull($this->positions->find('waste', 'P1'));
        $this->assertSame([], $this->outbox->list(new IntegrationOutboxFilter(limit: 0)));
        $this->assertNull($this->context->current());
        $this->assertNull($this->loggedUser->getForcedUser());
        $this->assertSame([], $subscriber->events());

        DomainEventPublisher::instance()->publish(new FakeEvent());
        $this->assertCount(1, $subscriber->events());

        $this->publisher->flush();
        $this->assertSame([], $this->outbox->list(new IntegrationOutboxFilter(limit: 0)));
    }

    public function testUnavailableIntegrationUserThrows(): void
    {
        $envelope = IntegrationEnvelopeMother::from();

        $this->expectException(IntegrationUserNotAvailable::class);

        try {
            $this->createSUT(username: 'unknown')($this->message($envelope));
        } finally {
            $this->assertSame([], $this->handler->calls());
            $this->assertFalse($this->inbox->has($envelope->id));
        }
    }

    /** @param list<IntegrationEventHandler>|null $handlers */
    private function createSUT(
        ?IntegrationSettings $settings = null,
        ?array $handlers = null,
        string $username = 'integration',
    ): IncomingIntegrationMessageHandler {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static fn(callable $func): mixed => $func());
        $entityManager->method('isOpen')->willReturn(true);

        return new IncomingIntegrationMessageHandler(
            $settings ?? IntegrationSettingsMother::enabled(),
            $entityManager,
            $this->createMock(ManagerRegistry::class),
            $this->inbox,
            $this->positions,
            new IntegrationUserProvider($this->userRepository, $username),
            $this->context,
            $this->publisher,
            $this->loggedUser,
            $this->loggedAccount,
            $handlers ?? [$this->handler],
            new SpyLogger(),
        );
    }

    private function message(IntegrationEnvelope $envelope): IncomingIntegrationMessage
    {
        return new IncomingIntegrationMessage($envelope);
    }

    private function inboxMessage(string $messageId): IntegrationInboxMessage
    {
        foreach ($this->inbox->list(new IntegrationInboxFilter(limit: 0)) as $message) {
            if ($message->messageId() === $messageId) {
                return $message;
            }
        }

        $this->fail(sprintf('Inbox message "%s" not found', $messageId));
    }
}
