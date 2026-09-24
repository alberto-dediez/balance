<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Application\User\RequestTwoFactorChallenge\RequestTwoFactorChallengeRequest;
use App\Security\Application\User\RequestTwoFactorChallenge\RequestTwoFactorChallengeResponse;
use App\Security\Application\User\RequestTwoFactorChallenge\RequestTwoFactorChallengeService;
use App\Security\Application\User\VerifyTwoFactorChallenge\VerifyTwoFactorChallengeRequest;
use App\Security\Application\User\VerifyTwoFactorChallenge\VerifyTwoFactorChallengeService;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorVerifiedBadge;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class TwoFactorLoginController extends AbstractController
{
    use TargetPathTrait;

    private const FAILURE_MESSAGE = 'Two-factor verification failed. Please sign in again.';

    public function __construct(
        private RateLimiterFactory $twoFactorCodeLimiter,
        private string $defaultLocale,
    ) {}

    #[Route('/login/2fa', name: 'app_login_two_factor', methods: ['GET'])]
    public function show(
        TokenStorageInterface $tokenStorage,
        LoggedUser $loggedUser,
        RequestTwoFactorChallengeService $requestChallenge,
        Security $security,
        Request $request,
    ): Response {
        if (!$tokenStorage->getToken() instanceof TwoFactorToken) {
            return $this->redirectToRoute('app_login');
        }

        $user = $loggedUser();

        try {
            $challenge = $requestChallenge(new RequestTwoFactorChallengeRequest($user->id()), $user);
        } catch (TwoFactorNotPendingException) {
            return $this->failAndReturnToLogin($security, $request);
        }

        return $this->renderChallenge($challenge);
    }

    #[Route('/login/2fa', name: 'app_login_two_factor_verify', methods: ['POST'])]
    public function verify(
        TokenStorageInterface $tokenStorage,
        LoggedUser $loggedUser,
        RequestTwoFactorChallengeService $requestChallenge,
        VerifyTwoFactorChallengeService $verifyChallenge,
        Security $security,
        Request $request,
    ): Response {
        $token = $tokenStorage->getToken();

        if (!$token instanceof TwoFactorToken) {
            return $this->redirectToRoute('app_login');
        }

        $limiter = $this->twoFactorCodeLimiter->create($request->getClientIp() ?? 'unknown');

        if (!$limiter->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $loggedUser();

        try {
            if (!$this->isCsrfTokenValid('two-factor', $request->request->getString('_token'))) {
                return $this->renderChallenge($requestChallenge(new RequestTwoFactorChallengeRequest($user->id()), $user), error: 'csrf');
            }

            if ('resend' === $request->request->getString('action')) {
                $challenge = $requestChallenge(new RequestTwoFactorChallengeRequest($user->id(), force: true), $user);

                return $this->renderChallenge($challenge, resent: TwoFactorMethod::Email === $challenge->method);
            }

            $verifyChallenge(new VerifyTwoFactorChallengeRequest($user->id(), $request->request->getString('code')), $user);
        } catch (TwoFactorCodeInvalidException $e) {
            if ($e->isLocked()) {
                return $this->failAndReturnToLogin($security, $request);
            }

            $challenge = $requestChallenge(new RequestTwoFactorChallengeRequest($user->id()), $user);

            return $this->renderChallenge($challenge, error: 'invalid', attemptsLeft: $e->attemptsLeft());
        } catch (TwoFactorNotPendingException) {
            return $this->failAndReturnToLogin($security, $request);
        }

        $targetPath = $this->getTargetPath($request->getSession(), $token->firewallName());

        $badges = [new TwoFactorVerifiedBadge()];

        if ($token->rememberMeRequested()) {
            $badges[] = (new RememberMeBadge())->enable();
        }

        $response = $security->login(new SecurityUser($user), 'form_login', $token->firewallName(), $badges);

        if (null === $targetPath || '' === $targetPath || !$response instanceof Response) {
            return $this->redirectToRoute('home', ['_locale' => $user->preferredLocale()?->value ?? $this->defaultLocale]);
        }

        return $response;
    }

    private function renderChallenge(
        RequestTwoFactorChallengeResponse $challenge,
        bool $resent = false,
        ?string $error = null,
        ?int $attemptsLeft = null,
    ): Response {
        return $this->render('@security/login/two-factor.html.twig', [
            'method' => $challenge->method->value,
            'maskedDestination' => $challenge->maskedDestination,
            'isFallback' => $challenge->isFallback,
            'attemptsLeft' => $attemptsLeft,
            'error' => $error,
            'resent' => $resent,
        ]);
    }

    private function failAndReturnToLogin(Security $security, Request $request): Response
    {
        $security->logout(false);

        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, new CustomUserMessageAuthenticationException(self::FAILURE_MESSAGE));

        return $this->redirectToRoute('app_login');
    }
}
