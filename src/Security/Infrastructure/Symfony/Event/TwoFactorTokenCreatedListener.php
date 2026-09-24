<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\SsoBadge;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorVerifiedBadge;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;
use Symfony\Component\Security\Http\ParameterBagUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[AsEventListener]
class TwoFactorTokenCreatedListener
{
    use TargetPathTrait;

    private const FIREWALL = 'main';

    private const REMEMBER_ME_PARAMETER = '_remember_me';

    public function __construct(
        private readonly EffectiveTwoFactorMethod $effectiveMethod,
        private readonly RequestStack $requestStack,
    ) {}

    public function __invoke(AuthenticationTokenCreatedEvent $event): void
    {
        $passport = $event->getPassport();
        $token = $event->getAuthenticatedToken();

        if ($passport->hasBadge(TwoFactorVerifiedBadge::class)) {
            return;
        }

        $user = $token->getUser();

        if ($token instanceof RememberMeToken || !$user instanceof SecurityUser) {
            return;
        }

        $method = match (true) {
            $passport->hasBadge(PasswordCredentials::class) => $this->effectiveMethod->resolve($user),
            $passport->hasBadge(SsoBadge::class) => $this->effectiveMethod->resolveForSso($user),
            default => null,
        };

        if (!$method instanceof TwoFactorMethod) {
            return;
        }

        $rememberMeBadge = $passport->getBadge(RememberMeBadge::class);
        $rememberMe = $rememberMeBadge instanceof RememberMeBadge && ($rememberMeBadge->isEnabled() || $this->isRememberMeRequested($rememberMeBadge));

        $rememberMeBadge?->disable();

        $twoFactorToken = new TwoFactorToken($token, self::FIREWALL, $rememberMe);

        $targetPath = $this->targetPath();

        if (null !== $targetPath) {
            $twoFactorToken->setAttribute(TwoFactorToken::TARGET_PATH_ATTRIBUTE, $targetPath);
        }

        $event->setAuthenticatedToken($twoFactorToken);
    }

    private function isRememberMeRequested(RememberMeBadge $badge): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        return (bool) filter_var(ParameterBagUtils::getRequestParameterValue($request, self::REMEMBER_ME_PARAMETER, $badge->parameters), \FILTER_VALIDATE_BOOL);
    }

    private function targetPath(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            return null;
        }

        $targetPath = $this->getTargetPath($request->getSession(), self::FIREWALL);

        return null === $targetPath || '' === $targetPath ? null : $targetPath;
    }
}
