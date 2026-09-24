<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Notification;

use App\Security\Domain\Model\User\TwoFactorChallengeRequested;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use Symfony\Contracts\Translation\TranslatorInterface;

class TwoFactorCodeNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof TwoFactorChallengeRequested);

        $this->notify($event);
    }

    private function notify(TwoFactorChallengeRequested $event): void
    {
        $plainCode = $event->challenge()->plainCode();

        if ('' === $plainCode) {
            return;
        }

        $user = $this->userRepository->find($event->userId());

        if (!$user instanceof User) {
            return;
        }

        ($this->emailSender)(
            recipient: new EmailRecipient(name: $user->username(), email: $user->username()),
            subject: $this->translator->trans('Código de acceso'),
            template: '@security/two-factor/code.email.html.twig',
            params: [
                'otpCode' => $plainCode,
                'expiresAt' => $event->expiresAt(),
            ],
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof TwoFactorChallengeRequested;
    }
}
