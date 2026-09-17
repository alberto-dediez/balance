<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Event;

use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsEventListener]
class CsrfProtectedListener
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function __invoke(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        foreach ($event->getAttributes(CsrfProtected::class) as $attribute) {
            $token = $request->query->getString($attribute->parameter, $request->request->getString($attribute->parameter));

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($attribute->tokenId, $token))) {
                throw new AccessDeniedHttpException('Invalid CSRF token.');
            }
        }
    }
}
