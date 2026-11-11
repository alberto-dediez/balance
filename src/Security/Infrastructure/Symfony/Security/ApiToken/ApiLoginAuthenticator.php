<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\ApiToken;

use App\Security\Application\ApiToken\Authenticate\AuthenticateWithApiTokenRequest;
use App\Security\Application\ApiToken\Authenticate\AuthenticateWithApiTokenService;
use App\Security\Application\ApiToken\MarkAsUsed\MarkApiTokenAsUsedRequest;
use App\Security\Application\ApiToken\MarkAsUsed\MarkApiTokenAsUsedService;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

#[AsDecorator(decorates: 'security.authenticator.json_login.api_login')]
final class ApiLoginAuthenticator implements InteractiveAuthenticatorInterface
{
    public function __construct(
        #[AutowireDecorated]
        private readonly InteractiveAuthenticatorInterface $inner,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AuthenticateWithApiTokenService $authenticateWithApiToken,
        private readonly MarkApiTokenAsUsedService $markApiTokenAsUsed,
        private readonly JWTTokenManagerInterface $jwtManager,
        #[Autowire(service: 'lexik_jwt_authentication.handler.authentication_success')]
        private readonly AuthenticationSuccessHandler $successHandler,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $this->inner->supports($request);
    }

    public function authenticate(Request $request): Passport
    {
        $passport = $this->inner->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        $passwordCredentials = $passport->getBadge(PasswordCredentials::class);

        assert($userBadge instanceof UserBadge);
        assert($passwordCredentials instanceof PasswordCredentials);

        $apiTokenBadge = new ApiTokenBadge();

        $checker = function (#[\SensitiveParameter] string $secret, UserInterface $user) use ($apiTokenBadge): bool {
            if ('' === $secret) {
                return false;
            }

            if ($user instanceof SecurityUser && $this->passwordHasher->isPasswordValid($user, $secret)) {
                return true;
            }

            if (!$user instanceof SecurityUser) {
                return false;
            }

            $authenticateWithApiToken = $this->authenticateWithApiToken;

            $response = $authenticateWithApiToken(new AuthenticateWithApiTokenRequest($secret), $user);

            if (null === $response->apiTokenId) {
                return false;
            }

            $apiTokenBadge->authenticatedWith($response->apiTokenId);

            return true;
        };

        return new Passport($userBadge, new CustomCredentials($checker, $passwordCredentials->getPassword()), [$apiTokenBadge]);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $token = $this->inner->createToken($passport, $firewallName);

        $apiTokenBadge = $passport->hasBadge(ApiTokenBadge::class) ? $passport->getBadge(ApiTokenBadge::class) : null;

        if ($apiTokenBadge instanceof ApiTokenBadge && null !== $apiTokenBadge->apiTokenId()) {
            $token->setAttribute(ApiTokenBadge::ATTRIBUTE, (string) $apiTokenBadge->apiTokenId());
        }

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$token->hasAttribute(ApiTokenBadge::ATTRIBUTE) || !$user instanceof UserInterface) {
            return $this->inner->onAuthenticationSuccess($request, $token, $firewallName);
        }

        $apiTokenId = (string) $token->getAttribute(ApiTokenBadge::ATTRIBUTE);

        if ($user instanceof SecurityUser) {
            $markApiTokenAsUsed = $this->markApiTokenAsUsed;

            $markApiTokenAsUsed(new MarkApiTokenAsUsedRequest(new ApiTokenId($apiTokenId)), $user);
        }

        $jwt = $this->jwtManager->createFromPayload($user, [ApiTokenBadge::ATTRIBUTE => $apiTokenId]);

        return $this->successHandler->handleAuthenticationSuccess($user, $jwt);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->inner->onAuthenticationFailure($request, $exception);
    }

    public function isInteractive(): bool
    {
        return $this->inner->isInteractive();
    }
}
