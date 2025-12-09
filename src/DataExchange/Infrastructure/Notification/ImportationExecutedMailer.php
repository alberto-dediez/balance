<?php

namespace App\DataExchange\Infrastructure\Notification;

use App\DataExchange\Domain\Model\Importation\ImportationExecuted;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailSender;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Event\DomainEventSubscriber;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportationExecutedMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private ImportationRepository $importationRepository,
        private UserRepository $userRepository,
        private TranslatorInterface $translator
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event)
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException("Incorrect event!");
        }

        $this->handleImportationExecuted($event);
    }

    private function handleImportationExecuted(ImportationExecuted $event)
    {
        $importation = $this->importationRepository->find($event->importationId());

        if (null === $importation) {
            return ;
        }

        $user = $this->userRepository->find($importation->registeredBy());

        if (null === $user) {
            return ;
        }

        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $user->username(), email: $user->username()),
            subject: $this->translator->trans('Importación de {file} finalizada', ['{file}' => $importation->name()]),
            template: '@data_exchange/notification/executed.email.html.twig',
            params: [
                'user' => $user,
                'importation' => $importation
            ]
        );
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return $event instanceof ImportationExecuted;
    }
}
