<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Notification;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationFailed;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportationFailedMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private ExportationRepository $exportationRepository,
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof ExportationFailed) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->handleExportationFailed($event);
    }

    private function handleExportationFailed(ExportationFailed $event): void
    {
        $exportation = $this->exportationRepository->find($event->exportationId());

        if (!$exportation instanceof Exportation) {
            return;
        }

        $userId = $exportation->registeredBy();

        if (!$userId instanceof UserId) {
            return;
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return;
        }

        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $user->username(), email: $user->username()),
            subject: $this->translator->trans('La exportación no se ha podido generar'),
            template: '@data_exchange/notification/exportation-failed.email.html.twig',
            params: [
                'user' => $user,
                'exportation' => $exportation,
            ],
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ExportationFailed;
    }
}
