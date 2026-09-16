<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Security\Infrastructure\Sso\SsoProviderRegistry;
use App\Security\Infrastructure\Sso\UnknownSsoProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class SsoAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    public const LOCALE_SESSION_KEY = 'sso.locale';

    private const FAILURE_MESSAGE = 'SSO authentication could not be completed.';

    private const UNKNOWN_USER_MESSAGE = 'No user is registered with the identity provider email.';

    public function __construct(
        private readonly SsoProviderRegistry $providers,
        private readonly UserProvider $userProvider,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultLocale,
    ) {}

    public function supports(Request $request): bool
    {
        return 'app_login_sso_callback' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $key = (string) $request->attributes->get('provider');

        try {
            $provider = $this->providers->get($key);
        } catch (UnknownSsoProviderException $e) {
            throw new CustomUserMessageAuthenticationException(self::FAILURE_MESSAGE, [], 0, $e);
        }

        if (!$provider->isEnabled()) {
            throw new CustomUserMessageAuthenticationException(self::FAILURE_MESSAGE);
        }

        try {
            $identity = $provider->authenticate($request);
        } catch (SsoAuthenticationFailedException $e) {
            throw new CustomUserMessageAuthenticationException(self::FAILURE_MESSAGE, [], 0, $e);
        }

        return new SelfValidatingPassport(
            new UserBadge($identity->email, function (string $email): UserInterface {
                try {
                    return $this->userProvider->loadUserByIdentifier($email);
                } catch (UserNotFoundException $e) {
                    throw new CustomUserMessageAuthenticationException(self::UNKNOWN_USER_MESSAGE, [], 0, $e);
                }
            }),
            [(new RememberMeBadge())->enable()],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if (null !== $targetPath && '' !== $targetPath) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();
        $locale = $user instanceof SecurityUser ? ($user->preferredLocale()?->value ?? $this->defaultLocale) : $this->defaultLocale;

        return new RedirectResponse($this->urlGenerator->generate('home', ['_locale' => $locale]));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();
        $session->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        $locale = $session->get(self::LOCALE_SESSION_KEY);

        return new RedirectResponse($this->urlGenerator->generate('app_login', [
            '_locale' => is_string($locale) ? $locale : $this->defaultLocale,
        ]));
    }
}
