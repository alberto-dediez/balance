<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Infrastructure\SSO\SSOClient;
use App\Security\Infrastructure\SSO\SSOJwtDecoderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SSOAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    /**
     * @param UserProviderInterface<\Symfony\Component\Security\Core\User\UserInterface> $userProvider
     */
    public function __construct(
        private readonly SSOClient $ssoClient,
        private readonly UserProviderInterface $userProvider,
        private readonly SSOJwtDecoderInterface $jwtDecoder,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function supports(Request $request): ?bool
    {
        return 'app_login_sso_callback' === $request->attributes->get('_route')
            && $request->query->has('token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->query->get('token');

        if (null === $token) {
            throw new AuthenticationException('No SSO token provided.');
        }

        if (!$this->ssoClient->checkToken($token)) {
            throw new AuthenticationException('Invalid SSO token.');
        }

        try {
            $payload = $this->jwtDecoder->decode($token);

            if (empty($payload)) {
                throw new AuthenticationException('Invalid JWT payload.');
            }

            $email = $payload['email'] ?? null;

            if (!$email) {
                throw new AuthenticationException('Email not found in SSO token.');
            }
        } catch (\Throwable $e) {
            throw new AuthenticationException('Failed to decode or validate SSO token: ' . $e->getMessage());
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function (string $userIdentifier) {
                try {
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                } catch (UserNotFoundException $e) {
                    throw new AuthenticationException('User not found in our system.');
                }
            }),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
