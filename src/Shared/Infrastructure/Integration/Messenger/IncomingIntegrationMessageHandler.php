<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration\Messenger;

use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Integration\IntegrationEventHandler;
use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Integration\IntegrationInboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationInboxStatus;
use App\Shared\Domain\Model\Integration\IntegrationPosition;
use App\Shared\Domain\Model\Integration\IntegrationPositionRepository;
use App\Shared\Infrastructure\Integration\BufferedIntegrationEventPublisher;
use App\Shared\Infrastructure\Integration\IntegrationContext;
use App\Shared\Infrastructure\Integration\IntegrationSettings;
use App\Shared\Infrastructure\Integration\IntegrationUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class IncomingIntegrationMessageHandler
{
    /** @param iterable<IntegrationEventHandler> $handlers */
    public function __construct(
        private readonly IntegrationSettings $settings,
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $managerRegistry,
        private readonly IntegrationInboxRepository $inbox,
        private readonly IntegrationPositionRepository $positions,
        private readonly IntegrationUserProvider $integrationUserProvider,
        private readonly IntegrationContext $context,
        private readonly BufferedIntegrationEventPublisher $publisher,
        private readonly LoggedUser $loggedUser,
        private readonly LoggedAccount $loggedAccount,
        #[AutowireIterator('shared.integration.handler')]
        private readonly iterable $handlers,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(IncomingIntegrationMessage $message): void
    {
        if (!$this->settings->enabled) {
            $this->logger->warning('Integration message received while integration events are disabled', [
                'id' => $message->envelope->id,
                'type' => $message->envelope->type,
            ]);

            return;
        }

        $event = $message->envelope->toReceivedEvent();

        if ($event->source === $this->settings->platform) {
            $this->logger->debug('Integration message from this platform ignored', ['id' => $event->id]);

            return;
        }

        try {
            $this->entityManager->wrapInTransaction(function () use ($event): void {
                $this->process($event);
            });
        } catch (\Throwable $exception) {
            DomainEventPublisher::instance()->clearPending();
            $this->publisher->discard();

            if (!$this->entityManager->isOpen()) {
                $this->managerRegistry->resetManager();
            }

            throw $exception;
        } finally {
            $this->context->leave();
            $this->loggedUser->forceUser(null);
            $this->loggedAccount->reset();
        }
    }

    private function process(ReceivedIntegrationEvent $event): void
    {
        if ($this->inbox->has($event->id)) {
            $this->logger->debug('Integration message already processed', ['id' => $event->id]);

            return;
        }

        if ($event->hops > $this->settings->maxHops) {
            $this->record($event, IntegrationInboxStatus::SkippedHops);

            return;
        }

        $handlers = $this->handlersFor($event);

        if ([] === $handlers) {
            $this->record($event, IntegrationInboxStatus::SkippedUnknownType);

            return;
        }

        $position = null === $event->aggregateId ? null : $this->positions->find($event->source, $event->aggregateId);

        if ($position instanceof IntegrationPosition && $event->sequence <= $position->lastSequence()) {
            $this->record($event, IntegrationInboxStatus::SkippedStale);

            return;
        }

        $user = $this->integrationUserProvider->user();
        $this->loggedUser->forceUser($user);
        $this->loggedAccount->reset();
        $this->context->enter($event);
        DomainEventPublisher::instance()->lock();

        foreach ($handlers as $handler) {
            $handler->handle($event, $user);
        }

        DomainEventPublisher::instance()->unlock();
        $this->publisher->flush();

        if (null !== $event->aggregateId) {
            $now = new \DateTimeImmutable('NOW');

            if ($position instanceof IntegrationPosition) {
                $position->advance($event->sequence, $now);
            } else {
                $position = new IntegrationPosition($event->source, $event->aggregateId, $event->sequence, $now);
            }

            $this->positions->save($position);
        }

        $this->record($event, IntegrationInboxStatus::Processed);
    }

    /** @return list<IntegrationEventHandler> */
    private function handlersFor(ReceivedIntegrationEvent $event): array
    {
        $handlers = [];

        foreach ($this->handlers as $handler) {
            if ($handler->supports($event->type, $event->version)) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }

    private function record(ReceivedIntegrationEvent $event, IntegrationInboxStatus $status): void
    {
        $this->inbox->add(new IntegrationInboxMessage(
            messageId: $event->id,
            source: $event->source,
            type: $event->type,
            version: $event->version,
            aggregateId: $event->aggregateId,
            sequence: $event->sequence,
            correlationId: $event->correlationId,
            causationId: $event->causationId,
            hops: $event->hops,
            status: $status,
            receivedAt: new \DateTimeImmutable('NOW'),
        ));
    }
}
