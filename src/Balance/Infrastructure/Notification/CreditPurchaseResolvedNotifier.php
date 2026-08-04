<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRejected;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseValidated;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class CreditPurchaseResolvedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectRepository $projectRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        match (true) {
            $event instanceof CreditPurchaseValidated => $this->handleCreditPurchaseValidated($event),
            $event instanceof CreditPurchaseRejected => $this->handleCreditPurchaseRejected($event),
            default => throw new \RuntimeException('Incorrect event!'),
        };
    }

    private function handleCreditPurchaseValidated(CreditPurchaseValidated $event): void
    {
        $params = [
            'requestedTonnes' => $event->requestedTonnes(),
            'purchasedTonnes' => $event->purchasedTonnes(),
            'amount' => $event->amount(),
            'reference' => $event->reference(),
            ...$this->projectParamsFor($event->creditPurchaseId()),
        ];

        $template = $event->isPartiallyFulfilled()
            ? '@balance/notification/credit-purchase-partially-validated'
            : '@balance/notification/credit-purchase-validated';

        $subject = $event->isPartiallyFulfilled()
            ? (string) new TranslatableMessage('Su compra de créditos ha sido validada parcialmente')
            : (string) new TranslatableMessage('Su compra de créditos ha sido validada');

        $this->notifyRequester($event->creditPurchaseId(), $subject, $template, $params);
    }

    private function handleCreditPurchaseRejected(CreditPurchaseRejected $event): void
    {
        $params = [
            'reason' => $event->reason(),
        ];

        $this->notifyRequester(
            $event->creditPurchaseId(),
            (string) new TranslatableMessage('Su solicitud de créditos ha sido rechazada'),
            '@balance/notification/credit-purchase-rejected',
            $params,
        );
    }

    /** @param array<string,mixed> $params */
    private function notifyRequester(
        CreditPurchaseId $creditPurchaseId,
        string $subject,
        string $templateBaseName,
        array $params,
    ): void {
        $requester = $this->requesterFor($creditPurchaseId);

        if (null === $requester) {
            return;
        }

        $this->notifier->notify(new SystemNotification(
            recipientId: $requester->id(),
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: $templateBaseName . '.notification.html.twig',
            params: $params,
            accountId: $requester->accountId(),
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $requester->id(),
            channel: NotificationChannel::Email,
            subject: $subject,
            template: $templateBaseName . '.email.html.twig',
            params: $params,
            accountId: $requester->accountId(),
        ));
    }

    private function requesterFor(CreditPurchaseId $creditPurchaseId): ?User
    {
        $creditPurchase = $this->creditPurchaseRepository->find($creditPurchaseId);

        if (null === $creditPurchase || null === $creditPurchase->requestedBy()) {
            return null;
        }

        return $this->userRepository->find($creditPurchase->requestedBy());
    }

    /** @return array{projectName: ?string, projectMitecoId: ?string} */
    private function projectParamsFor(CreditPurchaseId $creditPurchaseId): array
    {
        $creditPurchase = $this->creditPurchaseRepository->find($creditPurchaseId);

        $project = null !== $creditPurchase?->projectId()
            ? $this->projectRepository->find($creditPurchase->projectId())
            : null;

        return [
            'projectName' => $project?->name(),
            'projectMitecoId' => $project?->mitecoId(),
        ];
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CreditPurchaseValidated || $event instanceof CreditPurchaseRejected;
    }
}
