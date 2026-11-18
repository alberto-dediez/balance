<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Live;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Live\LiveTopics;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Jwt\Grant;

class LiveSubscriptionCookieSubscriber implements EventSubscriberInterface
{
    private const string FIREWALL = 'main';

    public function __construct(
        private readonly Authorization $authorization,
        private readonly Security $security,
        private readonly LoggedUser $loggedUser,
        private readonly LiveTopics $topics,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 10],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest() || $event->getResponse()->isRedirection()) {
            return;
        }

        $request = $event->getRequest();

        if (self::FIREWALL !== $this->security->getFirewallConfig($request)?->getName()) {
            return;
        }

        $user = $this->loggedUser->getLoggedUserOrNull();

        if (!$user instanceof User) {
            return;
        }

        try {
            $this->authorization->setCookie(
                $request,
                [new Grant([Grant::ACTION_SUBSCRIBE], $this->topics->for($user))],
                null,
                ['exp' => new \DateTimeImmutable('+1 hour')],
            );
        } catch (\Throwable $exception) {
            $this->logger->warning('The live subscription cookie could not be set', [
                'exception' => $exception,
            ]);
        }
    }
}
