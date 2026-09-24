<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsEventListener(priority: 10)]
class TwoFactorPendingAccessDeniedListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof AccessDeniedException) {
            return;
        }

        if (!$this->tokenStorage->getToken() instanceof TwoFactorToken) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login_two_factor', ['_locale' => LocaleGetter::locale()->value])));
    }
}
