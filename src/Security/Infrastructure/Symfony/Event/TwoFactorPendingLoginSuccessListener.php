<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[AsEventListener(priority: -48)]
class TwoFactorPendingLoginSuccessListener
{
    use TargetPathTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $token = $event->getAuthenticatedToken();

        if (!$token instanceof TwoFactorToken) {
            return;
        }

        $rememberMeBadge = $event->getPassport()->getBadge(RememberMeBadge::class);

        if ($rememberMeBadge instanceof RememberMeBadge) {
            $rememberMeBadge->disable();
        }

        $targetPath = $token->hasAttribute(TwoFactorToken::TARGET_PATH_ATTRIBUTE) ? $token->getAttribute(TwoFactorToken::TARGET_PATH_ATTRIBUTE) : null;

        if (is_string($targetPath) && $event->getRequest()->hasSession()) {
            $this->saveTargetPath($event->getRequest()->getSession(), $token->firewallName(), $targetPath);
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login_two_factor', ['_locale' => LocaleGetter::locale()->value])));
    }
}
